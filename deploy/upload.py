#!/usr/bin/env python3
"""上传文件到服务器"""
import sys, base64, tarfile, io, os

# Add deploy directory to path
sys.path.insert(0, 'C:/Users/admin/WorkBuddy/2026-08-25-16-30-21/nongxian-mall/deploy')
from ssh_tool import connect

def upload_directory(local_dir, remote_dir):
    """上传目录到服务器"""
    c = connect()

    # Create tar stream
    buf = io.BytesIO()
    with tarfile.open(fileobj=buf, mode='w:gz') as tar:
        tar.add(local_dir, arcname='.')
    buf.seek(0)
    data = buf.read()

    # Send via stdin
    channel = c.open_sftp()
    sftp = channel
    sftp.chdir(remote_dir)

    # List files and upload
    import subprocess
    result = subprocess.run(
        ['tar', 'czf', '-', '-C', local_dir, '.'],
        capture_output=True
    )
    if result.returncode != 0:
        print(f"tar error: {result.stderr.decode()}")
        return False

    # Write to remote file
    remote_file = f"{remote_dir}/upload.tar.gz"
    with sftp.open(remote_file, 'wb') as f:
        f.write(result.stdout)

    sftp.close()
    c.close()
    print(f"Uploaded {len(result.stdout)} bytes to {remote_file}")
    return True

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print("Usage: upload.py <local_dir> <remote_dir>")
        sys.exit(1)
    success = upload_directory(sys.argv[1], sys.argv[2])
    sys.exit(0 if success else 1)
