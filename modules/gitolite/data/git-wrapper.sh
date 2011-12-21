#!/bin/bash

export GIT_SSH="$(dirname ${0})/ssh-wrapper.sh"
cd "$(dirname ${0})/gitolite-admin"
git "${@}"
