import subprocess
result = subprocess.run(
    ['C:/xampp/mysql/bin/mysql.exe', '-u', 'root', '-e',
     'SELECT id, email, password, role FROM globetrek.users WHERE email="testuser@testing.com";'],
    capture_output=True, text=True
)
print(result.stdout)
print(result.stderr)
