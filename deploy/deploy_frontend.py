#!/usr/bin/env python3
"""部署 nongxian-mall 前端（h5 / admin）到生产服务器。

流程：本地 tar 打包 dist → 上传服务器 /tmp → 备份旧目录 → 解压覆盖 → 修正文件属主。
所有凭据通过环境变量注入，绝不硬编码；`.env` 已被仓库根 `.gitignore` 忽略。

用法：
  # 1) 复制模板并填写（切勿把真实密码提交进仓库）
  cp deploy/.env.example deploy/.env
  # 编辑 deploy/.env 填入 DEPLOY_PWD 等

  # 2) 部署（默认假设 dist 已构建好）
  python deploy/deploy_frontend.py --target h5
  python deploy/deploy_frontend.py --target admin
  python deploy/deploy_frontend.py --target all

  # 部署前顺便本地重新构建
  python deploy/deploy_frontend.py --target all --build

环境变量（也可直接 export，无需 .env）：
  DEPLOY_HOST         服务器地址（默认 204.141.218.37）
  DEPLOY_USER         SSH 用户（默认 root）
  DEPLOY_PWD          SSH 密码（必填）
  DEPLOY_REMOTE_ROOT 远端 web 根目录（默认 /var/www/nongxian-mall）
  DEPLOY_WWW_OWNER    解压后修正的属主（默认 www-data:www-data）
"""
import os
import sys
import time
import tarfile
import tempfile
import argparse
import subprocess

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
from ssh_tool import connect  # noqa: E402

HOST = os.environ.get("DEPLOY_HOST", "204.141.218.37")
USER = os.environ.get("DEPLOY_USER", "root")
PWD = os.environ.get("DEPLOY_PWD", "")
REMOTE_ROOT = os.environ.get("DEPLOY_REMOTE_ROOT", "/var/www/nongxian-mall")
WWW_OWNER = os.environ.get("DEPLOY_WWW_OWNER", "www-data:www-data")

REPO = os.path.dirname(HERE)
TARGETS = {
    "h5": os.path.join(REPO, "h5", "dist"),
    "admin": os.path.join(REPO, "admin", "dist"),
}


def pack(local_dir):
    tmp = tempfile.NamedTemporaryFile(suffix=".tar.gz", delete=False)
    tmp.close()
    with tarfile.open(tmp.name, "w:gz") as tar:
        tar.add(local_dir, arcname=".")
    return tmp.name


def deploy(target, backup=True):
    local = TARGETS[target]
    if not os.path.isdir(local):
        sys.stderr.write(
            f"[!] 本地构建目录不存在：{local}\n"
            f"    请先在该目录执行 `npm run build`（或加 --build 参数）\n"
        )
        sys.exit(1)

    remote = REMOTE_ROOT.rstrip("/") + "/" + target
    print(f"[*] 打包 {local}")
    tar_path = pack(local)
    print(f"[*] 上传到 {HOST}:{remote}")

    c = connect()
    try:
        stamp = int(time.time())
        remote_tar = f"/tmp/nongxian_{target}_{stamp}.tar.gz"
        sftp = c.open_sftp()
        sftp.put(tar_path, remote_tar)
        sftp.close()

        backup_step = f"cp -r {remote} {remote}.bak.{stamp} && " if backup else ""
        cmd = (
            f"{backup_step}"
            f"rm -rf {remote}/* && mkdir -p {remote} && "
            f"tar -xzf {remote_tar} -C {remote} && "
            f"rm -f {remote_tar} && "
            f"chown -R {WWW_OWNER} {remote} && "
            f"echo '--- deployed ---' && ls -la {remote} && "
            f"echo '--- assets count ---' && ls {remote}/assets 2>/dev/null | wc -l"
        )
        stdin, stdout, stderr = c.exec_command(cmd, timeout=300)
        sys.stdout.write(stdout.read().decode("utf-8", "replace"))
        err = stderr.read().decode("utf-8", "replace")
        if err:
            sys.stderr.write(err)
    finally:
        c.close()
        os.remove(tar_path)

    print(f"[✓] {target} 部署完成")


def main():
    ap = argparse.ArgumentParser(description="部署 nongxian-mall 前端到生产服务器")
    ap.add_argument("--target", choices=["h5", "admin", "all"], default="all",
                    help="部署哪一个前端（默认 all）")
    ap.add_argument("--build", action="store_true",
                    help="部署前先在本地执行 npm install + npm run build")
    ap.add_argument("--no-backup", action="store_true",
                    help="不备份远端旧目录（谨慎使用）")
    args = ap.parse_args()

    if not PWD:
        sys.stderr.write("DEPLOY_PWD 未设置：请在 deploy/.env 或环境变量中提供\n")
        sys.exit(2)

    targets = ["h5", "admin"] if args.target == "all" else [args.target]
    for t in targets:
        if args.build:
            print(f"[*] 构建 {t} ...")
            subprocess.run(["npm", "install", "--legacy-peer-deps"],
                           cwd=os.path.join(REPO, t), check=True)
            subprocess.run(["npm", "run", "build"],
                           cwd=os.path.join(REPO, t), check=True)
        deploy(t, backup=not args.no_backup)


if __name__ == "__main__":
    main()
