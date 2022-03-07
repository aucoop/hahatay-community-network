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

## Nextcloud
First of all, is needed to add the local IP range or domain as a trusted domain. Since we use nextcloud in our local network and it's not exposed to the outside, it's fine to add local IP range as a trusted domain. To do so:

Edit `config.php` inside the nextcloud volume (`nextcloud/nextcloud/config/config.php`) in the host machine. Admin permission is needed to edit the file. Add the following line:

In

```source
'trusted_domains' =>
  array (
    0 => 'localhost:8000',
  ),
```

We will add the local IP range as a trusted domain:

```source
'trusted_domains' =>
  array (
    0 => 'localhost:8000',
    1 => '192.168.*.*',
  ),
```

We also can add domains:

```source
'trusted_domains' =>
  array (
    0 => 'localhost:8000',
    1 => '192.168.*.*',
    2 => 'server.hahatay',
  ),
```

That configuration should work for most local network since most routers assign this range to the local network.

Now we're ready to connect there from any client in the same network as the server.

## Pihole

When setting up the DNS server, most likely the host will complain because there is already a DNS server running. In Ubuntu server `bind` is listening to port `53` by default, and pihole will complain about that. To fix this, I found out this answer from [pihole forum](https://discourse.pi-hole.net/t/docker-unable-to-bind-to-port-53/45082/7):

```source
Port 53 is being used at your host machine, that's why you can not bind 53 to host.

To find what is using port 53 you can do: sudo lsof -i -P -n | grep LISTEN

I'm a 99.9% sure that systemd-resolved is what is listening to port 53. To solve that you need to disable it. You can do that with these 2 commands:

    systemctl disable systemd-resolved.service
    systemctl stop systemd-resolved

Now you have port 53 open, but no dns configured for your host. To fix that, you need to edit '/etc/resolv.conf' and add the dns address. This is an example with a common dns address:

nameserver 8.8.8.8

If you have another nameserver in that file, I would comment it to prevent issues.
Once pihole docker container gets running, you can change the dns server of your host to localhost, as you are binding port 53 to the host machine. Change again '/etc/resolv.conf' like this

nameserver 127.0.0.1
```
