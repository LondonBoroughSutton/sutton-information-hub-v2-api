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
# $CF_SECRET_SERVICE = The name of the S3 bucket holding the .env files
# $CF_SECRET_SERVICE_KEY = The name of the service key that holds the secret S3 bucket access details
# $CF_APP_NAME = The name of the main app as stated in the manifest
# $TRAVIS_BUILD_DIR = The directory of the project.
# $TRAVIS_COMMIT = The commit hash of the build.

# Bail out on first error.
set -e

BLUE='\e[1;34m'
GREEN='\e[1;32m'
ENDCOLOUR='\e[1;m'

# ================================
# Remove once testing complete
source ${PWD}/.travis/envar

echo -e "${BLUE}Installing AWS CLI...${ENDCOLOUR}"
rm -Rf ${PWD}/aws
wget -q -O awscliv2.zip https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip
unzip awscliv2.zip
${PWD}/aws/install
aws --version
rm  awscliv2.zip

echo -e "${BLUE}Installing CloudFoundry CLI...${ENDCOLOUR}"
apt-get update && apt-get install -y --allow-unauthenticated gnupg
wget -q -O - https://packages.cloudfoundry.org/debian/cli.cloudfoundry.org.key | apt-key add -
echo "deb https://packages.cloudfoundry.org/debian stable main" | tee /etc/apt/sources.list.d/cloudfoundry-cli.list
apt-get update && apt-get install -y --allow-unauthenticated cf7-cli jq sed
# End Remove section
# ================================

# Set environment variables.
echo -e "${BLUE}Setting deployment configuration for ${ENVIRONMENT}...${ENDCOLOUR}"
export ENV_SECRET_FILE=".env.api.${ENVIRONMENT}"

# Connect to the Cloud Foundry API.
echo -e "${BLUE}Logging into Cloud Foundry...${ENDCOLOUR}"

# Login to Cloud Foundry.
cf login -a $CF_API -u $CF_USERNAME -p $CF_PASSWORD -o $CF_ORGANISATION -s $CF_SPACE

# Get the .env file from the secret S3 bucket
echo -e "${BLUE}Retreive the AWS S3 access credentials${ENDCOLOUR}"
cf service-key $CF_SECRET_SERVICE $CF_SECRET_SERVICE_KEY | sed -n '/{/,/}/p' | jq . > secret_access.json

cat secret_access.json

# Export the AWS S3 access credentials for use by the AWS CLI
export AWS_ACCESS_KEY_ID=`jq -r .aws_access_key_id secret_access.json`
export AWS_DEFAULT_REGION=`jq -r .aws_region secret_access.json`
export AWS_SECRET_ACCESS_KEY=`jq -r .aws_secret_access_key secret_access.json`
export AWS_BUCKET_NAME=`jq -r .bucket_name secret_access.json`
export AWS_DEFAULT_OUTPUT=json

# Remove the secret file
rm secret_access.json

echo -e "${BLUE}Retrieve the relevant dotenv file${ENDCOLOUR}"
aws s3api get-object --bucket ${AWS_BUCKET_NAME} --key ${ENV_SECRET_FILE} ${PWD}/.env

sed -i "s/AWS_ACCESS_KEY_ID=*/AWS_ACCESS_KEY_ID=$AWS_ACCESS_KEY_ID/" ${PWD}/.env
sed -i "s/AWS_SECRET_ACCESS_KEY=*/AWS_SECRET_ACCESS_KEY=$AWS_SECRET_ACCESS_KEY/" ${PWD}/.env
sed -i "s/AWS_DEFAULT_REGION=*/AWS_DEFAULT_REGION=$AWS_DEFAULT_REGION/" ${PWD}/.env

# Get the service parameters for the .env file
echo -e "${BLUE}Retrieve the VCAP_SERVICES environment variable${ENDCOLOUR}"
cf env ${CF_APP_NAME} | sed '1,/VCAP_SERVICES/d;/VCAP_APPLICATION/,$d' | sed '1 i\{' | jq . > services.json

cat services.json

# MySQL
echo -e "${BLUE}Update the MySQL connection values${ENDCOLOUR}"
DB_HOST=`jq -r .mysql[0].credentials.host services.json`
DB_PORT=`jq -r .mysql[0].credentials.port services.json`
DB_DATABASE=`jq -r .mysql[0].credentials.name services.json`
DB_USERNAME=`jq -r .mysql[0].credentials.username services.json`
DB_PASSWORD=`jq -r .mysql[0].credentials.password services.json`
sed -i "s/DB_HOST=*/DB_HOST=$DB_HOST/;s/DB_PORT=*/DB_PORT=$DB_PORT/;s/DB_DATABASE=*/DB_DATABASE=$DB_DATABASE/;s/DB_USERNAME=*/DB_USERNAME=$DB_USERNAME/;s/DB_PASSWORD=*/DB_PASSWORD=$DB_PASSWORD/" ${PWD}/.env

# Redis
echo -e "${BLUE}Update the Redis connection values${ENDCOLOUR}"
REDIS_HOST=`jq -r .redis[0].credentials.host services.json`
REDIS_PASSWORD=`jq -r .redis[0].credentials.password services.json`
REDIS_PORT=`jq -r .redis[0].credentials.port services.json`
sed -i "s/REDIS_HOST=*/REDIS_HOST=$REDIS_HOST/;s/REDIS_PASSWORD=*/REDIS_PASSWORD=$REDIS_PASSWORD/;s/REDIS_PORT=*/REDIS_PORT=$REDIS_PORT/" ${PWD}/.env

# Elasticsearch
echo -e "${BLUE}Update the Elasticsearch connection values${ENDCOLOUR}"
SCOUT_ELASTIC_HOST=`jq -r .elasticsearch[0].credentials.hostname services.json`
sed -i "s/SCOUT_ELASTIC_HOST=*/SCOUT_ELASTIC_HOST=$SCOUT_ELASTIC_HOST/" ${PWD}/.env

# SQS
echo -e "${BLUE}Update the SQS connection values${ENDCOLOUR}"
SQS_ACCESS_KEY_ID=`jq -r '."aws-sqs-queue"[0].credentials.aws_access_key_id' services.json`
SQS_SECRET_ACCESS_KEY=`jq -r '."aws-sqs-queue"[0].credentials.aws_secret_access_key' services.json`
SQS_DEFAULT_REGION=`jq -r '."aws-sqs-queue"[0].credentials.aws_region' services.json`
SQS_PREFIX=`jq -r '."aws-sqs-queue"[0].credentials.primary_queue_url' services.json`
sed -i "s/SQS_ACCESS_KEY_ID=*/SQS_ACCESS_KEY_ID=$SQS_ACCESS_KEY_ID/;s|SQS_SECRET_ACCESS_KEY=*|SQS_SECRET_ACCESS_KEY=$SQS_SECRET_ACCESS_KEY|;s/SQS_DEFAULT_REGION=*/SQS_DEFAULT_REGION=$SQS_DEFAULT_REGION/;s|SQS_PREFIX=*|SQS_PREFIX=$SQS_PREFIX|" ${PWD}/.env

# S3
echo -e "${BLUE}Update the S3 connection values${ENDCOLOUR}"
AWS_ACCESS_KEY_ID=`jq -r '."aws-sqs-bucket"[0].credentials.aws_access_key_id' services.json`
AWS_SECRET_ACCESS_KEY=`jq -r '."aws-sqs-bucket"[0].credentials.aws_secret_access_key' services.json`
AWS_DEFAULT_REGION=`jq -r '."aws-sqs-bucket"[0].credentials.aws_region' services.json`
AWS_BUCKET=`jq -r '."aws-sqs-bucket"[0].credentials.bucket_name' services.json`
sed -i "s/AWS_ACCESS_KEY_ID=*/AWS_ACCESS_KEY_ID=$AWS_ACCESS_KEY_ID/;s|AWS_SECRET_ACCESS_KEY=*|AWS_SECRET_ACCESS_KEY=$AWS_SECRET_ACCESS_KEY|;s/AWS_DEFAULT_REGION=*/AWS_DEFAULT_REGION=$AWS_DEFAULT_REGION/;s/AWS_BUCKET=*/AWS_BUCKET=$AWS_BUCKET/" ${PWD}/.env

# Remove the services file
rm services.json

# Deploy.
echo -e "${GREEN}Deploy the prepared app${ENDCOLOUR}"
cf push --var instances=$CF_INSTANCES --var route=$CF_ROUTE

# Remove the AWS client
rm -Rf ${PWD}/aws
