[![AGPL-3.0](https://img.shields.io/github/license/CIDgravity/nextcloud-cidgravity-gateway-app.svg)](https://github.com/CIDgravity/nextcloud-cidgravity-gateway-app/blob/master/LICENSE)

# CIDgravity gateway

This Nextcloud app allows to retrieve files and folder metadata on decentralized web using CIDgravity gateway services.
Access a new tab for file details to view additional information about the file on IPFS.

## A simple app to retrieve files and folder from decentralized web

![](screenshots/app1.png)

## Requirements
* Nextcloud 29

## Installation
### Nextcloud app store (*recommended*)
Just install the app from the [Nextcloud app store](https://apps.nextcloud.com/apps). 
It can be found under the 'tools' category.

### Manual installation
* Download the latest version from the [release page](https://github.com/CIDgravity/nextcloud-cidgravity-gateway-app/releases).
* Extract the archive to your Nextcloud's app folder, e.g. `tar xvf cidgravity-gateway-x.x.x.tar.gz -C /path/to/nextcloud/apps`
* Enable the app in the Apps section of your Nextcloud.

### Install from git
* Simply clone the repo to your apps folder and build the frontend:

```
cd /path/to/nextcloud/apps/
git clone https://github.com/CIDgravity/nextcloud-cidgravity-gateway-app.git
cd nextcloud-cidgravity-gateway-app/
make composer
make npm-init
make build-js-production
```

* Enable the app in the Apps section of your Nextcloud.