#!/usr/bin/env bash

# Requires the following environment variables:
# $TRAVIS_BRANCH = The name of the git branch that the build is running on.
# $REPO_URI = The URI of the ECR repo to push to.
# $CLUSTER = The name of the ECS cluster to deploy to.
# $BLACKFIRE_SERVER_ID = The Blackfire server ID.
# $BLACKFIRE_SERVER_TOKEN = The Blackfire server token.

# Bail out on first error.
set -e

# Get the environment from the branch.
case ${TRAVIS_BRANCH} in
    master )
        ENVIRONMENT=production
        ;;
    develop )
        ENVIRONMENT=staging
        ;;
esac

# Declare the configuration variables for the deployment.
echo "Setting deployment configuration for ${DEPLOYMENT}..."
export ENV_SECRET_ID=".env.api.${ENVIRONMENT}"

# Build the image.
./docker/build.sh \
  --build-arg BLACKFIRE_SERVER_ID=$BLACKFIRE_SERVER_ID \
  --build-arg BLACKFIRE_SERVER_TOKEN=$BLACKFIRE_SERVER_TOKEN

# Deploy the update to the services.
SERVICE="api" ./docker/deploy
SERVICE="scheduler" ./docker/deploy
SERVICE="queue-worker" ./docker/deploy
