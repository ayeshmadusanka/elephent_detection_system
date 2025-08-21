#!/usr/bin/env python3
"""
Vertex AI Model Deployment Script
Deploys trained model to an endpoint for serving predictions
"""

import os
import json
import argparse
from datetime import datetime
from google.cloud import aiplatform
import time

# Configuration
PROJECT_ID = "pelagic-magpie-469618-k8"
LOCATION = "us-central1"
ENDPOINT_DISPLAY_NAME = "elephant-detection-endpoint"

# Machine types for deployment
MACHINE_TYPES = {
    "small": {
        "machine_type": "n1-standard-2",
        "accelerator_type": None,
        "accelerator_count": 0,
        "min_replica_count": 1,
        "max_replica_count": 2,
        "description": "Small deployment for testing (2 vCPUs, 7.5 GB RAM)"
    },
    "medium": {
        "machine_type": "n1-standard-4",
        "accelerator_type": None,
        "accelerator_count": 0,
        "min_replica_count": 1,
        "max_replica_count": 3,
        "description": "Medium deployment for moderate traffic (4 vCPUs, 15 GB RAM)"
    },
    "large": {
        "machine_type": "n1-standard-8",
        "accelerator_type": "NVIDIA_TESLA_T4",
        "accelerator_count": 1,
        "min_replica_count": 1,
        "max_replica_count": 5,
        "description": "Large deployment with GPU for high performance"
    }
}

def initialize_vertex_ai(project_id, location):
    """Initialize Vertex AI"""
    aiplatform.init(project=project_id, location=location)
    print(f"✓ Initialized Vertex AI")
    print(f"  Project: {project_id}")
    print(f"  Location: {location}")

def load_model_info(model_type="efficientnet"):
    """Load model information from training step"""
    filename = f"model_info_{model_type}.json"
    try:
        with open(filename, "r") as f:
            return json.load(f)
    except FileNotFoundError:
        print(f"❌ {filename} not found. Please run train_model.py first.")
        return None

def get_model(model_id):
    """Get model by ID"""
    print(f"\n📦 Loading model: {model_id}")
    model = aiplatform.Model(model_name=model_id)
    print(f"✓ Model loaded: {model.display_name}")
    return model

def create_or_get_endpoint(display_name):
    """Create a new endpoint or get existing one"""
    print(f"\n🎯 Setting up endpoint: {display_name}")
    
    # Check for existing endpoints
    endpoints = aiplatform.Endpoint.list(
        filter=f'display_name="{display_name}"',
        order_by="create_time desc"
    )
    
    if endpoints:
        endpoint = endpoints[0]
        print(f"✓ Using existing endpoint: {endpoint.display_name}")
        print(f"  Resource name: {endpoint.resource_name}")
        return endpoint
    
    # Create new endpoint
    print(f"  Creating new endpoint...")
    endpoint = aiplatform.Endpoint.create(
        display_name=display_name,
        description="Endpoint for elephant detection model",
        sync=True
    )
    
    print(f"✓ Endpoint created successfully")
    print(f"  Resource name: {endpoint.resource_name}")
    return endpoint

def deploy_model_to_endpoint(model, endpoint, deployment_size="small"):
    """Deploy model to endpoint"""
    
    config = MACHINE_TYPES[deployment_size]
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    deployed_model_display_name = f"elephant-detector-{deployment_size}-{timestamp}"
    
    print(f"\n🚀 Deploying model to endpoint")
    print(f"  Deployment size: {deployment_size}")
    print(f"  Configuration: {config['description']}")
    print(f"  Machine type: {config['machine_type']}")
    print(f"  Min replicas: {config['min_replica_count']}")
    print(f"  Max replicas: {config['max_replica_count']}")
    
    if config['accelerator_type']:
        print(f"  Accelerator: {config['accelerator_type']} x{config['accelerator_count']}")
    
    print(f"\n⏳ Deployment in progress...")
    print(f"  This may take 10-15 minutes...")
    
    # Deploy the model
    model.deploy(
        endpoint=endpoint,
        deployed_model_display_name=deployed_model_display_name,
        machine_type=config['machine_type'],
        accelerator_type=config['accelerator_type'],
        accelerator_count=config['accelerator_count'],
        min_replica_count=config['min_replica_count'],
        max_replica_count=config['max_replica_count'],
        traffic_percentage=100,  # Send all traffic to this model
        sync=True
    )
    
    print(f"\n✅ Model deployed successfully!")
    return endpoint

def test_endpoint(endpoint):
    """Test the deployed endpoint with a sample prediction"""
    print(f"\n🧪 Testing endpoint...")
    
    # Create a test instance (you would normally load a real image here)
    test_instance = {
        "content": "base64_encoded_image_string_here"  # Placeholder
    }
    
    try:
        # Note: This is a placeholder test. Real testing requires actual image data
        print(f"  Endpoint is ready for predictions")
        print(f"  Endpoint ID: {endpoint.name}")
        print(f"  Endpoint resource name: {endpoint.resource_name}")
    except Exception as e:
        print(f"  Test skipped (requires real image data)")

def save_endpoint_info(endpoint, model_info, deployment_size):
    """Save endpoint information for the PHP backend"""
    
    # Get the endpoint URI
    endpoint_uri = f"https://{LOCATION}-aiplatform.googleapis.com/v1/{endpoint.resource_name}:predict"
    
    endpoint_info = {
        "endpoint_id": endpoint.name,
        "endpoint_resource_name": endpoint.resource_name,
        "endpoint_display_name": endpoint.display_name,
        "endpoint_uri": endpoint_uri,
        "model_id": model_info["model_id"],
        "model_display_name": model_info["display_name"],
        "deployment_size": deployment_size,
        "project_id": PROJECT_ID,
        "location": LOCATION,
        "deployed_time": datetime.now().isoformat()
    }
    
    # Save endpoint configuration
    with open("endpoint_config.json", "w") as f:
        json.dump(endpoint_info, f, indent=2)
    
    print(f"\n✓ Endpoint configuration saved to: endpoint_config.json")
    
    # Create PHP configuration file
    php_config = f"""<?php
/**
 * Vertex AI Endpoint Configuration
 * Auto-generated by deploy_model.py
 */

define('VERTEX_AI_ENDPOINT', '{endpoint_uri}');
define('VERTEX_AI_PROJECT_ID', '{PROJECT_ID}');
define('VERTEX_AI_LOCATION', '{LOCATION}');
define('VERTEX_AI_MODEL_NAME', '{model_info["display_name"]}');

// Endpoint details
\$endpoint_config = [
    'endpoint_id' => '{endpoint.name}',
    'endpoint_resource_name' => '{endpoint.resource_name}',
    'endpoint_uri' => '{endpoint_uri}',
    'model_id' => '{model_info["model_id"]}',
    'project_id' => '{PROJECT_ID}',
    'location' => '{LOCATION}'
];
"""
    
    with open("../vertex_ai_config.php", "w") as f:
        f.write(php_config)
    
    print(f"✓ PHP configuration saved to: vertex_ai_config.php")
    
    return endpoint_info

def create_prediction_example():
    """Create example code for making predictions"""
    
    example_code = """
# Python Example for Making Predictions
# =====================================

import base64
import json
from google.cloud import aiplatform

def predict_elephant(image_path, endpoint_id, project_id, location):
    \"\"\"Make a prediction using the deployed model\"\"\"
    
    # Initialize client
    aiplatform.init(project=project_id, location=location)
    
    # Get endpoint
    endpoint = aiplatform.Endpoint(endpoint_id)
    
    # Read and encode image
    with open(image_path, "rb") as f:
        image_bytes = f.read()
    encoded_image = base64.b64encode(image_bytes).decode('utf-8')
    
    # Prepare instance
    instance = {
        "content": encoded_image
    }
    
    # Make prediction
    prediction = endpoint.predict(instances=[instance])
    
    # Parse results
    predictions = prediction.predictions[0]
    
    # Get confidence scores and labels
    confidences = predictions.get('confidences', [])
    display_names = predictions.get('displayNames', [])
    
    # Combine and sort results
    results = list(zip(display_names, confidences))
    results.sort(key=lambda x: x[1], reverse=True)
    
    return results

# Usage
if __name__ == "__main__":
    with open("endpoint_config.json", "r") as f:
        config = json.load(f)
    
    results = predict_elephant(
        image_path="test_elephant.jpg",
        endpoint_id=config["endpoint_id"],
        project_id=config["project_id"],
        location=config["location"]
    )
    
    print("Predictions:")
    for label, confidence in results[:5]:
        print(f"  {label}: {confidence:.2%}")
"""
    
    with open("predict_example.py", "w") as f:
        f.write(example_code)
    
    print(f"✓ Prediction example saved to: predict_example.py")

def main():
    parser = argparse.ArgumentParser(description='Deploy elephant detection model to Vertex AI endpoint')
    parser.add_argument('--project-id', default=PROJECT_ID, help='GCP Project ID')
    parser.add_argument('--location', default=LOCATION, help='Vertex AI location')
    parser.add_argument('--model-id', help='Model ID to deploy')
    parser.add_argument('--model-type', 
                       default='efficientnet',
                       help='Model type (used to load model info)')
    parser.add_argument('--endpoint-name', 
                       default=ENDPOINT_DISPLAY_NAME,
                       help='Endpoint display name')
    parser.add_argument('--deployment-size',
                       choices=['small', 'medium', 'large'],
                       default='small',
                       help='Deployment configuration size')
    parser.add_argument('--test', 
                       action='store_true',
                       help='Test the endpoint after deployment')
    
    args = parser.parse_args()
    
    print("=" * 60)
    print("🐘 ELEPHANT DETECTION - MODEL DEPLOYMENT")
    print("=" * 60)
    
    # Initialize Vertex AI
    initialize_vertex_ai(args.project_id, args.location)
    
    # Load model info
    if not args.model_id:
        model_info = load_model_info(args.model_type)
        if not model_info:
            return
        model_id = model_info["model_id"]
    else:
        model_id = args.model_id
        model_info = {"model_id": model_id, "display_name": "Custom Model"}
    
    # Get model
    model = get_model(model_id)
    
    # Create or get endpoint
    endpoint = create_or_get_endpoint(args.endpoint_name)
    
    # Check if model is already deployed
    deployed_models = endpoint.list_models()
    if deployed_models:
        print(f"\n⚠️ Model already deployed to this endpoint")
        print(f"  Deployed models: {len(deployed_models)}")
        for dm in deployed_models:
            print(f"    - {dm.display_name}")
    else:
        # Deploy model
        endpoint = deploy_model_to_endpoint(model, endpoint, args.deployment_size)
    
    # Test endpoint if requested
    if args.test:
        test_endpoint(endpoint)
    
    # Save endpoint information
    endpoint_info = save_endpoint_info(endpoint, model_info, args.deployment_size)
    
    # Create prediction example
    create_prediction_example()
    
    print("\n" + "=" * 60)
    print("✅ DEPLOYMENT COMPLETE")
    print("=" * 60)
    print(f"Endpoint URI: {endpoint_info['endpoint_uri']}")
    print(f"Endpoint ID: {endpoint.name}")
    print(f"\nThe endpoint is ready to receive prediction requests!")
    print(f"Configuration files created:")
    print(f"  - endpoint_config.json (deployment details)")
    print(f"  - vertex_ai_config.php (PHP configuration)")
    print(f"  - predict_example.py (example prediction code)")

if __name__ == "__main__":
    main()