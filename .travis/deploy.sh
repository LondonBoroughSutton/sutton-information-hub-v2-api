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
# $CF_ENV_SERVICE = The name of the S3 bucket holding the .env files
# $CF_ENV_SERVICE_KEY = The name of the service key that holds the access credentials
# $CF_ASSET_SERVICE = The name of the S3 bucket holding asset files
# $CF_ASSET_SERVICE_KEY = The name of the service key that holds the access credentials
# $CF_APP_NAME = The name of the main app as stated in the manifest
# $TRAVIS_BUILD_DIR = The directory of the project.
# $TRAVIS_COMMIT = The commit hash of the build.
# $GITHUB_TOKEN = GitHub personal Access Token

# Bail out on first error.
set -e

BLUE='\e[1;34m'
GREEN='\e[1;32m'
ENDCOLOUR='\e[1;m'

# Set environment variables.
echo -e "${BLUE}Setting deployment configuration for ${ENVIRONMENT}...${ENDCOLOUR}"
export ENV_SECRET_FILE=".env.api.${ENVIRONMENT}"
export PUBLIC_KEY_SECRET="oauth-public.key.${ENVIRONMENT}"
export PRIVATE_KEY_SECRET="oauth-private.key.${ENVIRONMENT}"

# Connect to the Cloud Foundry API.
echo -e "${BLUE}Logging into Cloud Foundry...${ENDCOLOUR}"

# Login to Cloud Foundry.
cf login -a "$CF_API" -u "$CF_USERNAME" -p "$CF_PASSWORD" -o "$CF_ORGANISATION" -s "$CF_SPACE"

# Get the .env file from the secret S3 bucket
echo -e "${BLUE}Retreive the AWS S3 access credentials${ENDCOLOUR}"
cf service-key $CF_ENV_SERVICE $CF_ENV_SERVICE_KEY | sed -n '/{/,/}/p' | jq . > secret_access.json

# Export the AWS S3 access credentials for use by the AWS CLI
export AWS_ACCESS_KEY_ID=`jq -r .aws_access_key_id secret_access.json`
export AWS_DEFAULT_REGION=`jq -r .aws_region secret_access.json`
export AWS_SECRET_ACCESS_KEY=`jq -r .aws_secret_access_key secret_access.json`
export AWS_BUCKET_NAME=`jq -r .bucket_name secret_access.json`
export AWS_DEFAULT_OUTPUT=json

# Remove the secret file
rm secret_access.json

echo -e "${BLUE}Retrieve the relevant dotenv file${ENDCOLOUR}"
rm -f ${PWD}/.env
aws s3api get-object --bucket ${AWS_BUCKET_NAME} --key ${ENV_SECRET_FILE} ${PWD}/.env
echo -e "${BLUE}Retrieve the relevant Oauth public key${ENDCOLOUR}"
rm -f ${PWD}/storage/oauth-public.key
aws s3api get-object --bucket ${AWS_BUCKET_NAME} --key ${PUBLIC_KEY_SECRET} ${PWD}/storage/oauth-public.key
echo -e "${BLUE}Retrieve the relevant Oauth private key${ENDCOLOUR}"
rm -f ${PWD}/storage/oauth-private.key
aws s3api get-object --bucket ${AWS_BUCKET_NAME} --key ${PRIVATE_KEY_SECRET} ${PWD}/storage/oauth-private.key

# Get the service parameters for the .env file
echo -e "${BLUE}Retrieve the VCAP_SERVICES environment variable${ENDCOLOUR}"
cf env ${CF_APP_NAME} | sed '1,/VCAP_SERVICES/d;/VCAP_APPLICATION/,$d' | sed '1 i\{' | jq . > services.json

# SQS
echo -e "${BLUE}Update the SQS connection queues${ENDCOLOUR}"
SQS_PRIMARY_QUEUE_URL=`jq -r '."aws-sqs-queue"[0].credentials.primary_queue_url' services.json`
export SQS_PRIMARY_QUEUE=`echo "$SQS_PRIMARY_QUEUE_URL" | grep -Eo '|[^\/]+$|'`
SQS_SECONDARY_QUEUE_URL=`jq -r '."aws-sqs-queue"[0].credentials.secondary_queue_url' services.json`
export SQS_SECONDARY_QUEUE=`echo "$SQS_SECONDARY_QUEUE_URL" | grep -Eo '|[^\/]+$|'`

# S3
echo -e "${BLUE}Upload the S3 assets${ENDCOLOUR}"
# Export the AWS S3 access credentials for use by the AWS CLI
export AWS_ACCESS_KEY_ID=`jq -r '."aws-s3-bucket"[0].credentials.aws_access_key_id' services.json`
export AWS_DEFAULT_REGION=`jq -r '."aws-s3-bucket"[0].credentials.aws_region' services.json`
export AWS_SECRET_ACCESS_KEY=`jq -r '."aws-s3-bucket"[0].credentials.aws_secret_access_key' services.json`
export AWS_BUCKET_NAME=`jq -r '."aws-s3-bucket"[0].credentials.bucket_name' services.json`
export AWS_DEFAULT_OUTPUT=json

# Add the Elasticsearch files
aws s3api put-object --bucket ${AWS_BUCKET_NAME} --key 'elasticsearch/stop-words.csv' --body "$PWD/storage/cloud/elasticsearch/stop-words.csv"
aws s3api put-object --bucket ${AWS_BUCKET_NAME} --key 'elasticsearch/thesaurus.csv' --body "$PWD/storage/cloud/elasticsearch/thesaurus.csv"

# Remove the services file
rm services.json

# Deploy.
echo -e "${GREEN}Deploy the prepared app${ENDCOLOUR}"

if [ ! -z "$GITHUB_TOKEN" ]; then
    echo -e "${BLUE}Set the GitHub access token${ENDCOLOUR}"
    cf set-env ${CF_APP_NAME} COMPOSER_GITHUB_OAUTH_TOKEN "$GITHUB_TOKEN"
fi
cf push --var instances=$CF_INSTANCES --var route=$CF_ROUTE --var queue1=$SQS_PRIMARY_QUEUE --var queue2=$SQS_SECONDARY_QUEUE

# Remove the AWS client
rm -Rf ${PWD}/aws
