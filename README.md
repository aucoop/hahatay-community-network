# Hahatay Network

## About

[Hahhatay](https://hahatay.org) is a non-profit organization for community development, aimed at providing effective responses to forced migrations in Africa that deprive its youth of hope. The organization's objective is to empower and involve young people to become true protagonists in improving their lives, and to be a meeting space that connects individuals committed to integral human development.

## Network Implementation

Hahatay created a local network using six airMAX antennas, routers, hubs, switches, and two servers. The network connects four locations: Aminata (the cultural center), Defaratt (the recycling center), Sunu Keur (the residence), and Tabax Nité (the educational village). One server manages network traffic, hosts services and software to improve daily workflow, and the other server is exclusively dedicated to multimedia tasks.

In 2022, Hahatay added new access points and formed a mesh network to improve Wi-Fi access and implemented new local services. These included shared folders for workers with remote backup, online video and audio playback services, and an improved Nextcloud platform. Collaborating corporate entities donated servers for these services, including Labdoo and Typeform.

## Purpose

This repository aims to detail the process followed during the implementation of the Hahatay network in both years. The hope is to inspire more people from the tech community to embrace self-hosted projects and target other rural areas around the world. Documentation is focused on 3 main areas: backhaul network architecture, low cost Mesh Networks deployment and micro-services deployment.

### Low-Cost Mesh Networks
> **All documentation and code around Mesh Network deployment can be found [here](https://github.com/aucoop/self-hosted-docker-server/wiki)**

OpenWRT provides a good low cost option to deploy Mesh Networks, with a user friendly interface called LuCi. You will find documentation on how to:
- Install OpenWRT:
    - [Xiaomi Router 100MB](https://github.com/aucoop/self-hosted-docker-server/wiki/Install-OpenWrt-Xiaomi-Router-(100MB)) 
    - [Xiaomi Router 1GB](https://github.com/aucoop/self-hosted-docker-server/wiki/Install-OpenWrt-Xiaomi-Router-(1GB))
    - [Linksys Router 2500v4 E5400](https://github.com/aucoop/self-hosted-docker-server/wiki/Installing-OpenWRT-in-Linksys-2500v4---E5400-Routers)
    - [Linksys Router 2500v4 E5400 V2](https://github.com/aucoop/self-hosted-docker-server/wiki/Installing-OpenWRT-in-Linksys-2500v4-E5400-Routers-V2)
- [Create a Mesh Network](https://github.com/aucoop/self-hosted-docker-server/wiki/Setting-Up-Mesh-Network-with-OpenWrt-V2) 

### Microservices


> **All documentation and code around microservices deployment can be found [here](https://github.com/aucoop/self-hosted-docker-server/tree/documentation/office-server)**


Documentation  has been done around the following microservices deployed:

[Nextcloud](https://nextcloud.com): a cloud solution similar to Google Cloud, for uploading and sharing documents.

[Talk](https://nextcloud.com/talk/): a Nextcloud solution to chat with your peers and make voice and video calls.

A NAS with [OpenMediaVault](https://www.openmediavault.org): combined with backups in different buildings for disaster recovery.

[Navidrome](https://www.navidrome.org): streaming services for multimedia consumption.

[Traefik](https://traefik.io/): reverse proxy for deploying multiple microservices.

[Portainer](https://www.portainer.io/): for docker management.

[PiHole](https://pi-hole.net/): our Intranet DNS.

[SpeedTest](https://www.speedtest.net/): for internet-intranet speed testing.



