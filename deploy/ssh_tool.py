#!/usr/bin/env python3
"""Remote deploy helper for nongxian-mall.

Usage:
  DEPLOY_PWD='xxx' python ssh_tool.py exec "command"
  DEPLOY_PWD='xxx' python ssh_tool.py upload local_path remote_path
  DEPLOY_PWD='xxx' python ssh_tool.py download remote_path local_path

Env:
  DEPLOY_HOST  (default qiniu.ypvps.com)
  DEPLOY_USER  (default root)
  DEPLOY_PWD   (required)
"""
import os
import sys
import stat
import paramiko

HOST = os.environ.get("DEPLOY_HOST", "qiniu.ypvps.com")
USER = os.environ.get("DEPLOY_USER", "root")
PWD  = os.environ.get("DEPLOY_PWD", "")

def connect():
    if not PWD:
        sys.stderr.write("DEPLOY_PWD not set\n")
        sys.exit(2)
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect(HOST, username=USER, password=PWD, timeout=20, allow_agent=False, look_for_keys=False)
    return c

def run(c, cmd, timeout=120):
    stdin, stdout, stderr = c.exec_command(cmd, timeout=timeout, get_pty=False)
    out = stdout.read().decode("utf-8", "replace")
    err = stderr.read().decode("utf-8", "replace")
    rc   = stdout.channel.recv_exit_status()
    if out:
        sys.stdout.write(out)
        if not out.endswith("\n"):
            sys.stdout.write("\n")
    if err:
        sys.stderr.write(err)
        if not err.endswith("\n"):
            sys.stderr.write("\n")
    sys.stdout.write(f"[exit={rc}]\n")
    return rc

def upload(c, local, remote):
    sftp = c.open_sftp()
    # if local is dir, recursive upload
    import os.path as op
    def put_dir(l, r):
        try:
            sftp.stat(r)
        except FileNotFoundError:
            sftp.mkdir(r)
        for name in os.listdir(l):
            lp = op.join(l, name)
            rp = r.rstrip("/") + "/" + name
            if op.isdir(lp):
                put_dir(lp, rp)
            else:
                sftp.put(lp, rp)
                sys.stdout.write(f"up {rp}\n")
    if op.isdir(local):
        put_dir(local, remote)
    else:
        sftp.put(local, remote)
        sys.stdout.write(f"up {remote}\n")
    sftp.close()

def download(c, remote, local):
    sftp = c.open_sftp()
    sftp.get(remote, local)
    sftp.close()
    sys.stdout.write(f"got {local}\n")

def main():
    if len(sys.argv) < 3:
        sys.stderr.write(__doc__)
        sys.exit(2)
    action = sys.argv[1]
    c = connect()
    try:
        if action == "exec":
            cmd = sys.argv[2]
            rc = run(c, cmd, timeout=600)
            sys.exit(rc)
        elif action == "upload":
            upload(c, sys.argv[2], sys.argv[3])
        elif action == "download":
            download(c, sys.argv[2], sys.argv[3])
        else:
            sys.stderr.write("unknown action\n")
            sys.exit(2)
    finally:
        c.close()

if __name__ == "__main__":
    main()
