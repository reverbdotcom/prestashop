#! /usr/bin/env bash
if [ "$#" -ne 1 ]; then
  echo "Must specify a version"
  exit 1
fi
cwd=$(pwd)
cd src; zip -r "$cwd"/reverb-v"$1".zip .
