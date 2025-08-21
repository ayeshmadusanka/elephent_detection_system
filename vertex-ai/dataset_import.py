#!/usr/bin/env python3
"""
Vertex AI Dataset Import Script
Imports elephant dataset from Google Cloud Storage for training
"""

import os
import json
import argparse
from google.cloud import aiplatform
from google.cloud import storage
import time

# Configuration
PROJECT_ID = "pelagic-magpie-469618-k8"  # Update with your project ID
LOCATION = "us-central1"  # Update with your preferred location
BUCKET_NAME = "prasa_bucket"
DATASET_PATH = "Elephant_Dataset_Finalized"
DATASET_DISPLAY_NAME = "elephant-detection-dataset"

def initialize_vertex_ai(project_id, location):
    """Initialize Vertex AI with project and location"""
    aiplatform.init(project=project_id, location=location)
    print(f"✓ Initialized Vertex AI")
    print(f"  Project: {project_id}")
    print(f"  Location: {location}")

def create_import_file(bucket_name, dataset_path, output_file="import_data.jsonl"):
    """
    Create import file for Vertex AI dataset
    Lists all images in the bucket and creates JSONL format
    """
    print(f"\n📦 Creating import file from gs://{bucket_name}/{dataset_path}")
    
    storage_client = storage.Client()
    bucket = storage_client.bucket(bucket_name)
    
    # List all image files in the dataset path
    blobs = bucket.list_blobs(prefix=dataset_path)
    
    import_data = []
    image_count = 0
    
    for blob in blobs:
        # Filter for image files only
        if blob.name.lower().endswith(('.jpg', '.jpeg', '.png', '.webp', '.bmp')):
            # Extract label from folder structure if available
            # Assuming structure: Elephant_Dataset_Finalized/label/image.jpg
            path_parts = blob.name.split('/')
            
            if len(path_parts) > 2:
                label = path_parts[-2]  # Get parent folder as label
            else:
                label = "elephant"  # Default label
            
            # Create import data entry
            gcs_uri = f"gs://{bucket_name}/{blob.name}"
            
            # For image classification
            entry = {
                "imageGcsUri": gcs_uri,
                "classificationAnnotation": {
                    "displayName": label
                }
            }
            
            import_data.append(entry)
            image_count += 1
    
    # Write to JSONL file
    with open(output_file, 'w') as f:
        for entry in import_data:
            f.write(json.dumps(entry) + '\n')
    
    print(f"✓ Created import file: {output_file}")
    print(f"  Total images found: {image_count}")
    
    # Upload import file to GCS
    upload_blob = bucket.blob(f"import_files/{output_file}")
    upload_blob.upload_from_filename(output_file)
    import_file_uri = f"gs://{bucket_name}/import_files/{output_file}"
    
    print(f"✓ Uploaded import file to: {import_file_uri}")
    
    return import_file_uri, image_count

def create_dataset(display_name, metadata_schema_uri):
    """Create a Vertex AI dataset"""
    print(f"\n🗂️ Creating Vertex AI dataset: {display_name}")
    
    dataset = aiplatform.ImageDataset.create(
        display_name=display_name,
        metadata_schema_uri=metadata_schema_uri,
        sync=True
    )
    
    print(f"✓ Dataset created successfully")
    print(f"  Resource name: {dataset.resource_name}")
    print(f"  Display name: {dataset.display_name}")
    
    return dataset

def import_data_to_dataset(dataset, import_file_uri):
    """Import data into the dataset"""
    print(f"\n📥 Importing data into dataset...")
    print(f"  Import file: {import_file_uri}")
    
    dataset.import_data(
        gcs_source=[import_file_uri],
        import_schema_uri=aiplatform.schema.dataset.ioformat.image.single_label_classification,
        sync=True
    )
    
    print(f"✓ Data import completed successfully")

def check_existing_dataset(display_name):
    """Check if dataset already exists"""
    print(f"\n🔍 Checking for existing dataset: {display_name}")
    
    datasets = aiplatform.ImageDataset.list(
        filter=f'display_name="{display_name}"',
        order_by="create_time desc"
    )
    
    if datasets:
        print(f"✓ Found existing dataset")
        return datasets[0]
    
    print(f"  No existing dataset found")
    return None

def main():
    parser = argparse.ArgumentParser(description='Import elephant dataset to Vertex AI')
    parser.add_argument('--project-id', default=PROJECT_ID, help='GCP Project ID')
    parser.add_argument('--location', default=LOCATION, help='Vertex AI location')
    parser.add_argument('--bucket', default=BUCKET_NAME, help='GCS bucket name')
    parser.add_argument('--dataset-path', default=DATASET_PATH, help='Dataset path in bucket')
    parser.add_argument('--dataset-name', default=DATASET_DISPLAY_NAME, help='Dataset display name')
    parser.add_argument('--force-create', action='store_true', help='Force create new dataset')
    
    args = parser.parse_args()
    
    print("=" * 60)
    print("🐘 ELEPHANT DETECTION - VERTEX AI DATASET IMPORT")
    print("=" * 60)
    
    # Initialize Vertex AI
    initialize_vertex_ai(args.project_id, args.location)
    
    # Check for existing dataset
    dataset = None
    if not args.force_create:
        dataset = check_existing_dataset(args.dataset_name)
    
    if dataset is None:
        # Create import file
        import_file_uri, image_count = create_import_file(
            args.bucket, 
            args.dataset_path
        )
        
        if image_count == 0:
            print("❌ No images found in the specified path")
            return
        
        # Create new dataset
        dataset = create_dataset(
            display_name=args.dataset_name,
            metadata_schema_uri=aiplatform.schema.dataset.metadata.image
        )
        
        # Import data
        import_data_to_dataset(dataset, import_file_uri)
    
    # Display dataset information
    print("\n" + "=" * 60)
    print("✅ DATASET READY FOR TRAINING")
    print("=" * 60)
    print(f"Dataset ID: {dataset.name}")
    print(f"Display Name: {dataset.display_name}")
    print(f"Resource Name: {dataset.resource_name}")
    print(f"\nUse this dataset ID for training: {dataset.name}")
    
    # Save dataset info for later use
    dataset_info = {
        "dataset_id": dataset.name,
        "display_name": dataset.display_name,
        "resource_name": dataset.resource_name,
        "project_id": args.project_id,
        "location": args.location
    }
    
    with open("dataset_info.json", "w") as f:
        json.dump(dataset_info, f, indent=2)
    
    print(f"\n✓ Dataset info saved to: dataset_info.json")

if __name__ == "__main__":
    main()