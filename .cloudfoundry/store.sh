#!/usr/bin/env bash

# ================================
# Stores an object in the AWS S3 Secrets Bucket
# This script will install the AWS CLI and the CF CLI
# If you don't want these on your system, install using the docker helper script:
# ./develop bash cloudfoundry/store_env.sh
# ================================

# Requires the following environment variables:
# $CF_S3_SERVICE = The name of the S3 bucket to store the files
# $CF_S3_SERVICE_KEY = The name of the service key that holds the access credentials

# Can accept the following environment variables
# $CF_USERNAME = The Cloud Foundry username.
# $CF_PASSWORD = The Cloud Foundry password.
# $CF_ORGANISATION = The Cloud Foundry organisation.
# $CF_SPACE = The Cloud Foundry space.

# Bail out on first error.
set -e

# Set environment variables.
CF_API='https://api.cloud.service.gov.uk'
APPROOT=${APPROOT:-'/var/www/html'}

if [ -z "$CF_S3_SERVICE" ] || [ -z "$CF_S3_SERVICE_KEY" ]; then
    echo 'Missing variables for cf service-key'
    exit
fi

# Get the Cloud Foundry details
if [ -z "$CF_USERNAME" ]; then
    read -p 'Cloudfoundry Username: ' CF_USERNAME
fi

if [ -z "$CF_PASSWORD" ]; then
    read -sp 'Cloudfoundry Password: ' CF_PASSWORD
    echo
fi

if [ -z "$CF_ORGANISATION" ]; then
    read -p 'Cloudfoundry Organisation: ' CF_ORGANISATION
fi

if [ -z "$CF_SPACE" ]; then
    read -p 'Cloudfoundry Space: ' CF_SPACE
fi

# Install AWS CLI
echo "Installing AWS CLI..."
rm -Rf ${APPROOT}/aws
wget -q -O awscliv2.zip https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip
unzip awscliv2.zip
${APPROOT}/aws/install
aws --version
rm  awscliv2.zip

# Install CF
echo "Installing CloudFoundry CLI..."
apt-get update && apt-get install -y --allow-unauthenticated gnupg
wget -q -O - https://packages.cloudfoundry.org/debian/cli.cloudfoundry.org.key | apt-key add -
echo "deb https://packages.cloudfoundry.org/debian stable main" | tee /etc/apt/sources.list.d/cloudfoundry-cli.list
apt-get update && apt-get install -y --allow-unauthenticated cf7-cli jq

# Get the upload details
read -p 'Which environment is to be updated? (staging or production): ' ENVIRONMENT

if [ "$ENVIRONMENT" != 'staging' ] && [ "$ENVIRONMENT" != 'production' ]; then
    echo 'The environment should be one of staging or production'
    exit
fi

echo 'What is the path to the file? relative to the application root (e.g. .env, storage/cloud/files/public/...)'

read FILE_PATH

if [ ! -e "$APPROOT/$FILE_PATH" ]
then
    echo 'The file does not exist'
    exit
fi

if [[ $FILE_PATH == *"oauth-public.key"* ]]; then
    FILE_KEY="oauth-public.key.${ENVIRONMENT}"
elif [[ $FILE_PATH == *"oauth-private.key"* ]]; then
    FILE_KEY="oauth-private.key.${ENVIRONMENT}"
elif [[ $FILE_PATH == *".env"* ]]; then
    FILE_KEY=".env.api.${ENVIRONMENT}"
else
    read -p 'What is the path this file should be stored as? (e.g. files/public/abc123.png): ' FILE_KEY
fi

if [ -z "$FILE_KEY" ]; then
    echo 'The file does not match the type of file this script is for'
    exit
fi

# Check the user is happy to store the proposed update
read -p "Storing $FILE_PATH as $FILE_KEY Proceed? (Y/n): " PROCEED

PROCEED=${PROCEED:-'Y'}

if [ "$PROCEED" != 'Y'] && [ "$PROCEED" != 'y' ]; then
    echo "Aborting file storage"
    exit
fi

# Login to Cloud Foundry.
cf login -a $CF_API -u $CF_USERNAME -p $CF_PASSWORD -o $CF_ORGANISATION -s $CF_SPACE

# Get the .env file from the secret S3 bucket
cf service-key $CF_S3_SERVICE $CF_S3_SERVICE_KEY | sed -n '/{/,/}/p' | jq . > secret_access.json

# Export the AWS S3 access credentials for use by the AWS CLI
export AWS_ACCESS_KEY_ID=`jq -r .aws_access_key_id secret_access.json`
export AWS_DEFAULT_REGION=`jq -r .aws_region secret_access.json`
export AWS_SECRET_ACCESS_KEY=`jq -r .aws_secret_access_key secret_access.json`
export AWS_BUCKET_NAME=`jq -r .bucket_name secret_access.json`
export AWS_DEFAULT_OUTPUT=json

rm secret_access.json

echo "Uploading $APPROOT/$FILE_PATH to bucket $AWS_BUCKET_NAME as object $FILE_KEY"

aws s3api put-object --bucket ${AWS_BUCKET_NAME} --key "$FILE_KEY" --body "$APPROOT/$FILE_PATH"

# Remove the AWS client
rm -Rf ${PWD}/aws
