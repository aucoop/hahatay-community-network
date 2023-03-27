# Hahatay Community Network

## About

[Hahatay](https://hahatay.org) is a non-profit asociation for community development, aimed at providing effective responses to forced migrations in Africa that deprive its youth of hope. The organization's objective is to empower and involve young people to become true protagonists in improving their lives, and to be a meeting space that connects individuals committed to integral human development.

## Purpose

This repository aims to showcase the implementation details of the network topology. The goal is to show how we deployed the network, which hardware and software technology stack we used and how we manage the network in order to be open and transparent about the work done and inspire more people from the community networks community to embrace self-hosted projects and target other rural areas around the world. 

Documentation is focused on 3 main areas: backhaul network architecture, low cost Mesh Networks deployment and micro-services deployment.

## Thanks to Contributors

This is a non-profit project and it's driven mainly by students or volunteers that dedicate their free time on collaborate into tasks of the project.

The project is mainly funded by the CCD at the UPC (Universitat Politècnica de Catalunya)

TODO Collaborating corporate entities donated servers for these services, including Labdoo and Typeform.

## Network Implementation

### Backhaul Networks

The backhaul network interconnects geographically distributed areas using radio links (Ubiquiti airMAX antennas). At the edge, there is a small datacenter with three edge servers: one dedicated to network management functionalities and two others dedicated to perform multimedia tasks.

### Low-Cost Mesh Networks

The aforementioned geographically distributed areas have a surface that is big enough, so several access points are needed. In order to have proper WiFi access networks, we have built mesh networks using low-end routers.

All documentation and code around Mesh Network deployment can be found [here](https://github.com/aucoop/self-hosted-docker-server/wiki).

All of the routers and access points run OpenWrt. OpenWRT is an open source operating system for routers that provides an standarised way to deploy and manage networks, with a user-friendly graphical interface called LuCi. Some relevant documentation on:

* Install OpenWRT:
  * [Xiaomi Router 100MB](https://github.com/aucoop/self-hosted-docker-server/wiki/Install-OpenWrt-Xiaomi-Router-(100MB)).
  * [Xiaomi Router 1GB](https://github.com/aucoop/self-hosted-docker-server/wiki/Install-OpenWrt-Xiaomi-Router-(1GB))
  * [Linksys Router 2500v4 E5400](https://github.com/aucoop/self-hosted-docker-server/wiki/Installing-OpenWRT-in-Linksys-2500v4---E5400-Routers)
  * [Linksys Router 2500v4 E5400 V2](https://github.com/aucoop/self-hosted-docker-server/wiki/Installing-OpenWRT-in-Linksys-2500v4-E5400-Routers-V2).

* [Create a Mesh Network](https://github.com/aucoop/self-hosted-docker-server/wiki/Setting-Up-Mesh-Network-with-OpenWrt-V2) 

### Microservices

Services are deployed using microservices that run on containers. The container engine used is docker, and all the services are standalone and self-contained in docker-compose files. All documentation and code around microservices deployment can be found [here](https://github.com/aucoop/self-hosted-docker-server/tree/documentation/office-server).

The list of services that run in the network is constantly evolving, but here are some of the services that are currently running:

* [Nextcloud](https://nextcloud.com): a cloud solution similar to Google Cloud, for uploading and sharing documents.

* [Zabbix]() for monitoring of the network.

* [Nextcloud Talk](https://nextcloud.com/talk/): a Nextcloud solution to chat with your peers and make voice and video calls.

* Small NAS running in a raspberry pi with [penMediaVault](https://www.openmediavault.org): combined with backups in different buildings for disaster recovery.


* [Traefik](https://traefik.io/): reverse proxy for deploying multiple services in the same host. Also is useful to get SSL certificates within all services running in the network.

* [Portainer](https://www.portainer.io/): for container management.

* [PiHole](https://pi-hole.net/): our Intranet DNS. Check also the extra steps for having a redundant instance.

