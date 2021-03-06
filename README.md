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

First time, pull the image:

```source
docker pull jperon/pmb
```

Then run

```source
docker run --restart=always --name pmb -v pmb_data:/var/lib/mysql -v pmb_cfg:/etc/pmb -p 8080:80 -d jperon/pmb
```

For the first time, we need to go to http://localhost:8080/pmb/tables/install.php and configure the characteristics of the PMB. Reffer to the documentation for that.

The database credentials are:

nom d'utilisateur : `admin`
mot de passe : `admin`

All the stuff explained above, should only be done the first time that pmb is installed. After that, always you should enter to http://localhost:8080/pmb/

## Apache

Install apache by running:

`sudo apt install apache2`

Apache shows the stuff inside `/var/www/html`. In order to update the content from the folder, first open the file manager with admin permissions.  
To do so open the `terminal` (`terminal` can be opened with `Ctrl+Alt+T` shortcut). Then, type in the terminal `sudo nautilus` and then the password of the user will be asked. Enter it and then the file manager should be opened. Then, navigate to `/var/www/html` and there you can now copy paste whatever you want.

## Teamviewer

Download the `.deb` file from the teamviwer website and then install it by `sudo dpkg -i *.deb`.
