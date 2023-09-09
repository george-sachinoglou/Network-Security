# Network Security (AUEB Course)


:warning: **This is not a guide.**


The purpose of this project is to get used to the tools used for secure connection and encryption on the web. 
I created a Cent OS 7 (Linux) VM on [okeanos](https://okeanos.grnet.gr) and connected to it, using a client such as [PuTTY](https://www.putty.org/) but there will not be a step-to-step on how to set those up.
You can use the OS and ssh client of your choice.

The project is split into two subprojects:

## OpenSSL Contents
  
  [Set up users](#Set-up-users)
  - [Generate SSH key](#Generate-SSH-key)
  - [Create user](#Create-user)
  - [Add public keys](#Add-public-keys)
  
  [User permissions](#User-permissions)
  - [Modify permissions](#Modify-permissions)
  
  [Apache server initialization](#Apache-server-initialization)
  - [Install and start](#Install-and-start)
  - [Check-server](#Check-server)
  
  [Firewall](#Firewall)
  - [Enable http and https](#Enable-http-and-https)
  - [Remove password for ssh connection](#Remove-password-for-ssh-connection)
  
  [Create CA, CSR and SSL certificates](#Create-CA-CSR-and-SSL-certificates)
  - [Install OpenSSL](#Install-OpenSSL)
  - [Certificate Authority (CA)](#Certificate-Authority-CA)
  - [Generate key for SSL Certificates](#Generate-key-for-SSL-Certificates)
  
  [Apache configuration](#Apache-configuration)
  - [HTTP to HTTPS](#HTTP-to-HTTPS)
  - [Apply configuration](#Applying-configuration)
  
  [Create a site](#Create-a-site)
  -[Create site and configure httpd.conf](#Create-site-and-configure-httpd.conf)

## SQL Injection Contents

[Database Initialization and Security](#Database-Initialization-and-Security)
- [Install and start MariaDB](#Install-and-start-MariaDB)
- [Create Database](#Create-Database)

[PHP](#PHP)
- [Install PHP](#Install-PHP)

[Secure login](#Secure-login)
- [Configure securelogin.php](#EConfigure-securelogin.php)
  
# OpenSSL

## Set up users

> We need to set up a user and give them the ability to connect remotely with an ssh key they have generated,
> assuming they have provided us with their public key. We will also generate a personal ssh key and use it to
> connect with root, although this is not advised. This is a test project and we are not at risk of losing personal data.
> If youre doing this on your personal computer please use a password to connect with root.

### Generate SSH key
> Open cmd, generate key and fill out your information.
> In order to login with an ssh key through PuTTY we need to create a new 
```
ssh-keygen
~>ssh-keygen Generating public/private rsa key pair. 

```

### Create user

```
$ useradd user
$ passwd user
```

### Add public keys

> You should have a public key after generating your ssh key. Copy ONLY your public key and paste it as instructed below.
> Private keys should be kept PRIVATE.

```
$ mkdir /home/user/.ssh
$ mkdir ~/.ssh/
$ vi ~/.ssh/authorised_keys
```

## User permissions
> We need to give the proper permissions to the user(s) we have created.
> You can change the permissions as you see fit. The commands below only give read and execute permissions.

### Modify permissions

```
$ cd ..
$ chmod -R o+rx /home 
$ chmod -R o+rx /root
```

## Apache server initialization

> Setting up Apache web-server. Whenever a change is made do
```
$ systemctl restart sshd
```

### Install and start

```
$ yum install httpd
$ systemctl start httpd
```

### Check server

```
$ systemctl status httpd
$ systemctl status sshd
```

## Firewall

> Configuring firewall with http and https access.

### Enable http and https

```
$ firewall-cmd --permanent --add-service=http
$ firewall-cmd --permanent --add-service=https
```

### Remove password for ssh connection

> Go to

```
$ vi /etc/ssh/sshd_config
```

> And change

```
PasswordAuthentication no
```

## Create CA, CSR and SSL certificates

> Now that we are done setting up the VM we will use OpenSSL to generate the appropriate certificates.

### Install OpenSSL

```
$ yum install -y openssl
```

### Certificate Authority (CA)

> Create a new SSH Key and use it to create a CA. Fill out your information where needed.

```
$ cd /etc/pki/CA/private/
$ openssl genrsa -aes128 -out ourCA.key 2048
$ openssl req -new -x509 -days 365 -key /etc/pki/CA/private/ourCA.key -out /etc/pki/CA/certs/ourCA.crt
```

### Generate key for SSL Certificates

> We generate yet another key with OpenSSL and use it to create a CSR Certificate. Fill your information where needed.

```
$ openssl genrsa -out /etc/pki/tls/private/<server_name>.key 1024
$ openssl req -new -key /etc/pki/tls/private/<server_name>.key -out /etc/pki/tls/<server_name>.csr
```

> We send the CSR for a CA signature and then we return it back.

```
$ scp /etc/pki/tls/<server_name>.csr root@<server_name>:~/<server_name>.csr
$ openssl x509 -req -in <server_name>.csr -CA /etc/pki/CA/certs/ourCA.crt -CAkey /etc/pki/CA/private/ourCA.key -CAcreateserial -out <server_name>.crt -days 365
$ scp <server_name>.crt root@<server_name>:/etc/pki/tls/certs/<server_name>.crt
```
> Edit the following file and add the certificate paths as indicated.

```
$ vi /etc/httpd/conf.d/<server_hostname>.conf
```

```
<VirtualHost *:443>
    ServerName <server_hostname>
    DocumentRoot /var/www/html
    SSLEngine on
    SSLCertificateFile /etc/pki/tls/certs/<server_name>.crt
    SSLCertificateKeyFile /etc/pki/tls/private/<server_name>.key
</VirtualHost>
```

## Apache configuration

### HTTP to HTTPS

> Open the non-ssl.conf and add the following lines.

```
$ vi /etc/httpd/conf.d/non-ssl.conf
```

```
<VirtualHost *:80>
       ServerName <server_hostname>
        Redirect "/" "https://<server_hostname>/"
</VirtualHost>
```

### Apply configuration

```
$ systemctl restart httpd.service
```

## Create a site

> Create a simple login website with username and password fields at /var/www/html. 

### Create site and configure httpd.conf

> Edit the following file and make the necessary changes.

```
$ vi /etc/httpd/conf/httpd.conf
```

> Add this to the file.

```
DocumentRoot: /var/www/html
```

# SQL Injection

> We need a database to store our data and a proper encryption method for security.

## Database Initialization and Security

> We will use MariaDB for our database, but you can use MySQL or PostgreSQL as well.
> 
> We need to make sure the password is encrypted for privacy purposes and in case of illegal access to the database.
> Ways to achieve that is by:
> * Using an one-way function. Instead of the password being stored unchanged we will store the result of said function.
>   During login we insert the password to the one-way funtion and we compare it to the list elements for matching.
> * Using password salting to protect database from rainbow table attacks. The salt is added to the password table along
>   with the result of the one-way funtion. HashSalted(password) = hash(hash(password)+salt).
>
> We are going to use salt to protect our data.

### Install and start MariaDB

```
$ yum install mariadb-server mariadb-libs mariadb
$ sudo systemctl start mariadb
```

### Create Database

```
MariaDB [(none)]> CREATE DATABASE GDPR;
MariaDB [(none)]> USE GDPR;
```

```
MariaDB [(GDPR)]> CREATE TABLE users (
  username VARCHAR(255) PRIMARY KEY NOT NULL,
  password VARCHAR(255) NOT NULL,
  description VARCHAR(255),
  salt VARCHAR(255)  
    );
```

## PHP

> This section is named PHP but you can use any framework you wish.

### Install PHP

```
$ yum install php
```

## Secure login

> We will create the securelogin.php in /var/www/html which will run the queries necessary when attemting to login.
> This also contains the one way function we used to encrypt the passwords.


:warning: **This project does not include a sign up method. The users and their passwords were stored manually in the database.
The reason for this is that this project was used solely for learning about network security and storing items in a database did not seem necessary.**


### Configure securelogin.php

You can find the code [Here](/source/securelogin.php).



  
