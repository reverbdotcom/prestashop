#!/bin/sh -e

###########################################################
###   This script allows you to restart clean install quickly
############################################################
docker-compose stop
docker-compose rm -fv
sudo rm -Rf data/
sudo rm -Rf web/
docker-compose build --no-cache
docker-compose up -d

