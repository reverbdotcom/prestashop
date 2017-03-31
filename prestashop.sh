#!/bin/sh -e

#=============================================================================
#  Use this script build reverb images and run Reverb's containers
#
#==============================================================================
if [ "$1" = '' ] || [ "$1" = '--help' ];then
    printf "\n                                                                                  "
    printf "\n ================================================================================ "
    printf "\n                                  REVERB'S HELPER                                 "
    printf "\n                                                                                  "
    printf "\n For each commands, you may specify the prestashop version "16" or "17"           "
    printf "\n ================================================================================ "
    printf "\n                                                                                  "
    printf "\n                                                                                  "
    printf "\n      - init      : Build images and run containers (Delete existing volumes)     "
    printf "\n      - restart   : Run all containers if they already exist                      "
    printf "\n      - exec      : Bash prestashop.                                              "
    printf "\n      - log       : Log prestashop 1.6                                            "
    printf "\n                                                                                  "
fi

if [ "$1" = 'init' ] && [ "$2" = '' ];then
    docker-compose stop
    docker-compose rm -fv
    sudo rm -Rf data/
    sudo rm -Rf web
    docker-compose -f docker-compose.yml -f docker-compose.ps16.yml -f docker-compose.ps17.yml build --no-cache
    docker-compose -f docker-compose.yml -f docker-compose.ps16.yml -f docker-compose.ps17.yml up -d
fi

if [ "$1" = 'init17' ];then
    docker-compose stop
    docker-compose rm -fv
    sudo rm -Rf data/
    sudo rm -Rf web
    docker-compose -f docker-compose.yml -f  docker-compose.ps17.yml build --no-cache
    docker-compose -f docker-compose.yml -f docker-compose.ps17.yml up  -d
fi

if [ "$1" = 'restart' ];then
    docker-compose stop
    docker-compose -f docker-compose.yml -f docker-compose.ps16.yml -f docker-compose.ps17.yml up -d
fi

if [ "$1" = 'exec' ];then
    docker exec -it reverb"$2" bash
fi

if [ "$1" = 'log' ];then
    docker logs -f reverb"$2"
fi


