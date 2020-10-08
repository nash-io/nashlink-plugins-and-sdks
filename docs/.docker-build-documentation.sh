#!/bin/sh
#
# This script is used by Github workflows to build the documentation.
#

hugo --baseURL https://docs-link.nash.io --environment PROD
