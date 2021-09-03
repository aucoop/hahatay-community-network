#!/bin/bash 

if [ ! -d /var/www/html/pmb ]; then
  cd /var/www/html
  echo "Downloading PMB, please wait..."
  wget --no-check-certificate -q $PMB
  if (( $? != 0 )); then
    echo "Impossible to download PMB"
    exit 1
  fi
  PMB_DIR=$( basename `ls pmb*` )
  echo "$PMB_SHA $PMB_DIR" | sha256sum -c -
  if (( $? != 0 )); then
    echo "Invalid downloaded archive"
    exit 1
  fi
  echo "Extracting PMB, please wait..."
  unzip -q $PMB_DIR
  chown -R www-data:www-data pmb
  rm $PMB_DIR
fi

exec "$@"
