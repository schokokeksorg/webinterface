#!/bin/bash
/usr/bin/ssh -i "$(dirname ${0})/sshkey" "${@}"
