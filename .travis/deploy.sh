#!/usr/bin/env bash

# Requires the following environment variables:
# $ENVIRONMENT = The environment (production/release/staging).
# $CF_API = The URI of the Cloud Foundry instance.
# $CF_USERNAME = The Cloud Foundry username.
# $CF_PASSWORD = The Cloud Foundry password.
# $CF_ORGANISATION = The Cloud Foundry organisation.
# $CF_SPACE = The Cloud Foundry space.
# $CF_INSTANCES = The number of App instances required
# $CF_ROUTE = The public url of the app without the schema
# $TRAVIS_BUILD_DIR = The directory of the project.
# $TRAVIS_COMMIT = The commit hash of the build.

# Bail out on first error.
set -e

echo "PWD: $PWD"

source ${PWD}/.travis/envar

# ================================
# Remove once testing complete
echo "Installing AWS CLI..."
    rm -Rf ${PWD}/aws
    wget -q -O awscliv2.zip https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip
    unzip awscliv2.zip
    ${PWD}/aws/install
    aws --version

    echo "Installing CloudFoundry CLI..."
    apt-get update && apt-get install -y --allow-unauthenticated gnupg
    wget -q -O - https://packages.cloudfoundry.org/debian/cli.cloudfoundry.org.key | apt-key add -
    echo "deb https://packages.cloudfoundry.org/debian stable main" | tee /etc/apt/sources.list.d/cloudfoundry-cli.list
    apt-get update && apt-get install -y --allow-unauthenticated cf7-cli
# End Remove section
# ================================

# Set environment variables.
echo "Setting deployment configuration for ${ENVIRONMENT}..."
export ENV_SECRET_FILE=".env.api.${ENVIRONMENT}"

# Connect to the Cloud Foundry API.
echo "Logging into Cloud Foundry..."
# cf api $CF_API

echo "$CF_API"
echo "$CF_USERNAME"
echo "$CF_PASSWORD"
echo "$CF_ORGANISATION"
echo "$CF_SPACE"

# Login to Cloud Foundry.
cf login -a $CF_API -u $CF_USERNAME -p $CF_PASSWORD -o $CF_ORGANISATION -s $CF_SPACE

# Deploy.
# cf push --var instances=$CF_INSTANCES --var route=$CF_ROUTE
