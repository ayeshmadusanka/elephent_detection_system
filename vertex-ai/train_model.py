#!/usr/bin/env python3
"""
Vertex AI Model Training Script
Trains a custom image classification model for elephant detection
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
MODEL_DISPLAY_NAME = "elephant-detection-model"
TRAINING_DISPLAY_NAME = "elephant-detection-training"

# Training configurations for different model types
TRAINING_CONFIGS = {
    "efficientnet": {
        "display_name": "EfficientNet-B4 Elephant Detector",
        "model_type": "CLOUD_HIGH_ACCURACY_1",
        "node_hours": 8,  # Minimum for high accuracy
        "description": "High accuracy model using EfficientNet architecture"
    },
    "mobilenet": {
        "display_name": "MobileNet Elephant Detector", 
        "model_type": "CLOUD_LOW_LATENCY_1",
        "node_hours": 4,  # Minimum for low latency
        "description": "Fast inference model optimized for real-time detection"
    },
    "automl": {
        "display_name": "AutoML Elephant Detector",
        "model_type": "CLOUD",
        "node_hours": 8,
        "description": "AutoML optimized model with automatic architecture selection"
    }
}

def initialize_vertex_ai(project_id, location):
    """Initialize Vertex AI"""
    aiplatform.init(project=project_id, location=location)
    print(f"✓ Initialized Vertex AI")
    print(f"  Project: {project_id}")
    print(f"  Location: {location}")

def load_dataset_info():
    """Load dataset information from previous step"""
    try:
        with open("dataset_info.json", "r") as f:
            return json.load(f)
    except FileNotFoundError:
        print("❌ dataset_info.json not found. Please run dataset_import.py first.")
        return None

def get_dataset(dataset_id):
    """Get existing dataset by ID"""
    print(f"\n📊 Loading dataset: {dataset_id}")
    dataset = aiplatform.ImageDataset(dataset_name=dataset_id)
    print(f"✓ Dataset loaded: {dataset.display_name}")
    return dataset

def create_training_job(dataset, model_type="efficientnet", budget_hours=8):
    """Create and run a training job"""
    
    config = TRAINING_CONFIGS.get(model_type, TRAINING_CONFIGS["automl"])
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    job_display_name = f"{TRAINING_DISPLAY_NAME}_{model_type}_{timestamp}"
    
    print(f"\n🚀 Starting training job: {job_display_name}")
    print(f"  Model type: {config['display_name']}")
    print(f"  Training budget: {budget_hours} node hours")
    print(f"  Description: {config['description']}")
    
    # Create AutoML training job for image classification
    job = aiplatform.AutoMLImageTrainingJob(
        display_name=job_display_name,
        prediction_type="classification",
        multi_label=False,
        model_type=config["model_type"],
        base_model=None,  # Let AutoML choose
    )
    
    print(f"\n⏳ Training in progress...")
    print(f"  This may take several hours depending on dataset size and model complexity")
    
    # Run the training job
    model = job.run(
        dataset=dataset,
        model_display_name=f"{MODEL_DISPLAY_NAME}_{model_type}_{timestamp}",
        training_fraction_split=0.8,  # 80% for training
        validation_fraction_split=0.1,  # 10% for validation
        test_fraction_split=0.1,  # 10% for testing
        budget_milli_node_hours=budget_hours * 1000,  # Convert to milli node hours
        disable_early_stopping=False,
        sync=True  # Wait for completion
    )
    
    print(f"\n✅ Training completed successfully!")
    print(f"  Model resource name: {model.resource_name}")
    print(f"  Model display name: {model.display_name}")
    
    return model

def evaluate_model(model):
    """Get model evaluation metrics"""
    print(f"\n📈 Model Evaluation Metrics:")
    
    evaluations = model.list_model_evaluations()
    
    for evaluation in evaluations:
        metrics = evaluation.metrics
        
        if hasattr(metrics, 'au_prc'):
            print(f"  AU-PRC: {metrics.au_prc:.4f}")
        if hasattr(metrics, 'au_roc'):
            print(f"  AU-ROC: {metrics.au_roc:.4f}")
        if hasattr(metrics, 'log_loss'):
            print(f"  Log Loss: {metrics.log_loss:.4f}")
        
        # Get confusion matrix if available
        if hasattr(metrics, 'confusion_matrix'):
            cm = metrics.confusion_matrix
            print(f"\n  Confusion Matrix:")
            print(f"    True Positives: {cm.get('true_positives', 'N/A')}")
            print(f"    False Positives: {cm.get('false_positives', 'N/A')}")
            print(f"    True Negatives: {cm.get('true_negatives', 'N/A')}")
            print(f"    False Negatives: {cm.get('false_negatives', 'N/A')}")

def save_model_info(model, model_type):
    """Save model information for deployment"""
    model_info = {
        "model_id": model.name,
        "model_resource_name": model.resource_name,
        "display_name": model.display_name,
        "model_type": model_type,
        "project_id": PROJECT_ID,
        "location": LOCATION,
        "created_time": datetime.now().isoformat(),
        "artifact_uri": model.artifact_uri if hasattr(model, 'artifact_uri') else None
    }
    
    filename = f"model_info_{model_type}.json"
    with open(filename, "w") as f:
        json.dump(model_info, f, indent=2)
    
    print(f"\n✓ Model info saved to: {filename}")
    return model_info

def main():
    parser = argparse.ArgumentParser(description='Train elephant detection model on Vertex AI')
    parser.add_argument('--project-id', default=PROJECT_ID, help='GCP Project ID')
    parser.add_argument('--location', default=LOCATION, help='Vertex AI location')
    parser.add_argument('--dataset-id', help='Dataset ID (from dataset_import.py)')
    parser.add_argument('--model-type', 
                       choices=['efficientnet', 'mobilenet', 'automl'],
                       default='efficientnet',
                       help='Model architecture type')
    parser.add_argument('--budget-hours', 
                       type=int, 
                       default=8,
                       help='Training budget in node hours (minimum 4)')
    parser.add_argument('--evaluate', 
                       action='store_true',
                       help='Show model evaluation metrics after training')
    
    args = parser.parse_args()
    
    print("=" * 60)
    print("🐘 ELEPHANT DETECTION - MODEL TRAINING")
    print("=" * 60)
    
    # Initialize Vertex AI
    initialize_vertex_ai(args.project_id, args.location)
    
    # Load dataset info
    if not args.dataset_id:
        dataset_info = load_dataset_info()
        if not dataset_info:
            return
        dataset_id = dataset_info["dataset_id"]
    else:
        dataset_id = args.dataset_id
    
    # Get dataset
    dataset = get_dataset(dataset_id)
    
    # Train model
    model = create_training_job(
        dataset=dataset,
        model_type=args.model_type,
        budget_hours=args.budget_hours
    )
    
    # Evaluate model if requested
    if args.evaluate:
        try:
            evaluate_model(model)
        except Exception as e:
            print(f"⚠️ Could not retrieve evaluation metrics: {e}")
    
    # Save model information
    model_info = save_model_info(model, args.model_type)
    
    print("\n" + "=" * 60)
    print("✅ MODEL TRAINING COMPLETE")
    print("=" * 60)
    print(f"Model ID: {model.name}")
    print(f"Display Name: {model.display_name}")
    print(f"\nNext step: Run deploy_model.py to deploy this model")

if __name__ == "__main__":
    main()