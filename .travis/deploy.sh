#!/usr/bin/env bash

# Requires the following environment variables:
# $1 = The environment (production/release/staging).
# $2 = The URI of the ECR repo to push to.
# $3 = The name of the ECS cluster to deploy to.
# $4 = The AWS access key.
# $5 = The AWS secret access key.
# $6 = The AWS region.

# Bail out on first error.
set -e

# Set environment variables.
export ENV_SECRET_ID=".env.api.${1}"
export REPO_URI=${2}
export CLUSTER=${3}
export AWS_ACCESS_KEY_ID=${4}
export AWS_SECRET_ACCESS_KEY=${5}
export AWS_DEFAULT_REGION=${6}

# Build the image.
./docker/build.sh

# Deploy the update to the services.
SERVICE="api" ./docker/deploy.sh
SERVICE="scheduler" ./docker/deploy.sh
SERVICE="queue-worker" ./docker/deploy.sh
