#!/bin/sh

curl \
--include \
--request POST \
--header "application/x-www-form-urlencoded" \
--data-binary \
	"grant_type=client_credentials&client_id=${PUBLIC_KEY}&client_secret=${PRIVATE_KEY}" \
	'https://api.tcgplayer.com/token'