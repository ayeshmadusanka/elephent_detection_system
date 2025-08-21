#!/bin/bash

# Elephant Detection System - Vertex AI Setup Script
# This script automates the complete setup process

set -e  # Exit on any error

echo "=================================="
echo "🐘 ELEPHANT DETECTION SYSTEM SETUP"
echo "=================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_ID=${PROJECT_ID:-"pelagic-magpie-469618-k8"}
LOCATION=${LOCATION:-"us-central1"}
BUCKET_NAME=${BUCKET_NAME:-"prasa_bucket"}
DATASET_PATH=${DATASET_PATH:-"Elephant_Dataset_Finalized"}
MODEL_TYPE=${MODEL_TYPE:-"efficientnet"}
DEPLOYMENT_SIZE=${DEPLOYMENT_SIZE:-"small"}
BUDGET_HOURS=${BUDGET_HOURS:-8}

# Function to print colored output
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check prerequisites
check_prerequisites() {
    print_info "Checking prerequisites..."
    
    if ! command_exists gcloud; then
        print_error "Google Cloud SDK is not installed"
        echo "Install from: https://cloud.google.com/sdk/docs/install"
        exit 1
    fi
    
    if ! command_exists python3; then
        print_error "Python 3 is not installed"
        exit 1
    fi
    
    if ! command_exists pip; then
        print_error "pip is not installed"
        exit 1
    fi
    
    print_status "Prerequisites check passed"
}

# Setup Google Cloud
setup_gcloud() {
    print_info "Setting up Google Cloud..."
    
    # Check if authenticated
    if ! gcloud auth list --filter=status:ACTIVE --format="value(account)" | grep -q .; then
        print_warning "Not authenticated with Google Cloud"
        gcloud auth login
    fi
    
    # Set project
    gcloud config set project $PROJECT_ID
    print_status "Project set to: $PROJECT_ID"
    
    # Enable required APIs
    print_info "Enabling required APIs..."
    gcloud services enable aiplatform.googleapis.com
    gcloud services enable storage.googleapis.com
    gcloud services enable compute.googleapis.com
    
    print_status "Google Cloud setup completed"
}

# Install Python dependencies
install_dependencies() {
    print_info "Installing Python dependencies..."
    
    if [ -f "requirements.txt" ]; then
        pip install -r requirements.txt
        print_status "Dependencies installed"
    else
        print_error "requirements.txt not found"
        exit 1
    fi
}

# Check dataset in bucket
check_dataset() {
    print_info "Checking dataset in Google Cloud Storage..."
    
    if gsutil ls gs://$BUCKET_NAME/$DATASET_PATH/ >/dev/null 2>&1; then
        IMAGE_COUNT=$(gsutil ls gs://$BUCKET_NAME/$DATASET_PATH/**/*.jpg gs://$BUCKET_NAME/$DATASET_PATH/**/*.png gs://$BUCKET_NAME/$DATASET_PATH/**/*.jpeg 2>/dev/null | wc -l || echo "0")
        print_status "Dataset found with approximately $IMAGE_COUNT images"
    else
        print_error "Dataset not found at gs://$BUCKET_NAME/$DATASET_PATH/"
        print_warning "Please upload your dataset to Google Cloud Storage first"
        echo "Example: gsutil -m cp -r /path/to/your/images gs://$BUCKET_NAME/$DATASET_PATH/"
        exit 1
    fi
}

# Import dataset
import_dataset() {
    print_info "Importing dataset to Vertex AI..."
    
    python dataset_import.py \
        --project-id $PROJECT_ID \
        --location $LOCATION \
        --bucket $BUCKET_NAME \
        --dataset-path $DATASET_PATH \
        --dataset-name "elephant-detection-dataset"
    
    print_status "Dataset imported successfully"
}

# Train model
train_model() {
    print_info "Training model (this may take several hours)..."
    print_warning "Model type: $MODEL_TYPE, Budget: $BUDGET_HOURS hours"
    
    python train_model.py \
        --project-id $PROJECT_ID \
        --location $LOCATION \
        --model-type $MODEL_TYPE \
        --budget-hours $BUDGET_HOURS \
        --evaluate
    
    print_status "Model training completed"
}

# Deploy model
deploy_model() {
    print_info "Deploying model to endpoint..."
    print_warning "Deployment size: $DEPLOYMENT_SIZE"
    
    python deploy_model.py \
        --project-id $PROJECT_ID \
        --location $LOCATION \
        --model-type $MODEL_TYPE \
        --deployment-size $DEPLOYMENT_SIZE \
        --test
    
    print_status "Model deployed successfully"
}

# Verify setup
verify_setup() {
    print_info "Verifying setup..."
    
    # Check if endpoint config was generated
    if [ -f "../vertex_ai_config.php" ]; then
        print_status "PHP configuration file generated"
    else
        print_error "PHP configuration file not found"
        exit 1
    fi
    
    # Check if endpoint config JSON exists
    if [ -f "endpoint_config.json" ]; then
        print_status "Endpoint configuration saved"
    else
        print_error "Endpoint configuration not found"
        exit 1
    fi
    
    print_status "Setup verification completed"
}

# Main setup function
main() {
    echo "Configuration:"
    echo "  Project ID: $PROJECT_ID"
    echo "  Location: $LOCATION"
    echo "  Bucket: $BUCKET_NAME"
    echo "  Dataset Path: $DATASET_PATH"
    echo "  Model Type: $MODEL_TYPE"
    echo "  Budget Hours: $BUDGET_HOURS"
    echo "  Deployment Size: $DEPLOYMENT_SIZE"
    echo ""
    
    read -p "Continue with this configuration? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Setup cancelled"
        exit 1
    fi
    
    check_prerequisites
    setup_gcloud
    install_dependencies
    check_dataset
    
    # Ask for confirmation before expensive operations
    echo ""
    print_warning "The next steps will incur Google Cloud charges:"
    print_warning "  - Dataset import: Free"
    print_warning "  - Model training: ~\$25-50 (for $BUDGET_HOURS hours)"
    print_warning "  - Model deployment: ~\$1-5/day"
    echo ""
    read -p "Continue with training and deployment? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_warning "Stopping before training. You can run training manually later."
        import_dataset
        exit 0
    fi
    
    import_dataset
    train_model
    deploy_model
    verify_setup
    
    echo ""
    echo "=================================="
    print_status "SETUP COMPLETED SUCCESSFULLY!"
    echo "=================================="
    echo ""
    echo "Your elephant detection system is ready!"
    echo ""
    echo "Next steps:"
    echo "1. Copy your service account key to the project root"
    echo "2. Update .env file with the key filename"
    echo "3. Start the web server: php -S localhost:8000 app.php"
    echo "4. Open http://localhost:8000 in your browser"
    echo ""
    echo "Files generated:"
    echo "  - dataset_info.json (dataset details)"
    echo "  - model_info_$MODEL_TYPE.json (model details)"
    echo "  - endpoint_config.json (endpoint details)"
    echo "  - vertex_ai_config.php (PHP configuration)"
    echo "  - predict_example.py (prediction example)"
    echo ""
    print_warning "Remember: Keep your model endpoint deployed only when needed to avoid charges"
}

# Handle script arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --project-id)
            PROJECT_ID="$2"
            shift 2
            ;;
        --location)
            LOCATION="$2"
            shift 2
            ;;
        --bucket)
            BUCKET_NAME="$2"
            shift 2
            ;;
        --dataset-path)
            DATASET_PATH="$2"
            shift 2
            ;;
        --model-type)
            MODEL_TYPE="$2"
            shift 2
            ;;
        --budget-hours)
            BUDGET_HOURS="$2"
            shift 2
            ;;
        --deployment-size)
            DEPLOYMENT_SIZE="$2"
            shift 2
            ;;
        --help)
            echo "Usage: $0 [options]"
            echo ""
            echo "Options:"
            echo "  --project-id PROJECT_ID      Google Cloud Project ID"
            echo "  --location LOCATION          Vertex AI location (default: us-central1)"
            echo "  --bucket BUCKET_NAME         GCS bucket name"
            echo "  --dataset-path PATH          Dataset path in bucket"
            echo "  --model-type TYPE            Model type: efficientnet|mobilenet|automl"
            echo "  --budget-hours HOURS         Training budget in hours"
            echo "  --deployment-size SIZE       Deployment size: small|medium|large"
            echo "  --help                       Show this help message"
            exit 0
            ;;
        *)
            print_error "Unknown option: $1"
            exit 1
            ;;
    esac
done

# Run main setup
main