#! /usr/bin/env bash
if [ "$#" -ne 1 ]; then
  echo "Must specify a version"
  exit 1
fi
zip -r reverb-v"$1".zip src