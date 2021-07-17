import os

print("[+] Session tokens")

with open('users', 'r') as users:
	for j in users.readlines():
		os.system("php ./jwttt.php {} >> ./tokens".format(j.strip()))

with open('users', 'r') as users:
	for j in users.readlines():
		k = 15
		while k > 0:
			k -= 1
			os.system("php ./session_token.php {} >> ./PHPSESSIDs".format(j.strip()))
