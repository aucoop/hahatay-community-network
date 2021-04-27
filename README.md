# A Docker-Based Intranet 

## Before starting

To run the implementation, make sure you have installed **Docker Engine** and **Docker-compose**

If not, check the following instructions:
  * For **Docker** follow this [installation guide](https://docs.docker.com/install/linux/docker-ce/ubuntu/).

  * For **Docker-compose**, just run these commands:
    ```bash
    sudo curl -L "https://github.com/docker/compose/releases/download/1.22.0/docker-compose-$(uname -s)-$(uname -m)"  -o /usr/local/bin/docker-compose
    sudo mv /usr/local/bin/docker-compose /usr/bin/docker-compose
    sudo chmod +x /usr/bin/docker-compose
    ```

**Make sure** you don't need to type `sudo` every time you run docker. To do so, run the following command and then log out and in again:

```source
sudo usermod -aG docker $(whoami)
```

## PMB

[PMB software](https://www.sigb.net/index.php?lvl=cmspage&pageid=6&id_rubrique=220&opac_view=1) with Docker containers.

### Introduction

This project contains a docker-compose file to test quickly PMB software. It could be
interesting to test it before installing it on a server.

### How to

```bash
docker-compose up -d
```

It will start 3 services :
* the webserver
* the db engine 
* one to connect to the db

It could be long the first time (depending on your internet speeed). Check if
all services are okay with:

```bash
docker-compose ps
```

Normally all services are "up".

## Try PMB

The Docker exposed port is 8080, so in your browser go to
"http://localhost:8080/pmb/tables/install.php" and it will ask you to install pmb.
The information are :
* db host: db
* db name: pmb
* db root password : password

After that, PMB will be available at "http://localhost:8080/pmb".

## Change version of PMB

If you want to test another version of PMB, change the values in the .env file.

## Apache

Install apache by running:

`sudo apt install apache2`

Apache shows the stuff inside `/var/www/html`. In order to update the content from the folder, first open the file manager with admin permissions.  
To do so open the `terminal` (`terminal` can be opened with `Ctrl+Alt+T` shortcut). Then, type in the terminal `sudo nautilus` and then the password of the user will be asked. Enter it and then the file manager should be opened. Then, navigate to `/var/www/html` and there you can now copy paste whatever you want.

## Teamviewer

Download the `.deb` file from the teamviwer website and then install it by `sudo dpkg -i *.deb`.
