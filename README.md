# Shopify Import Project

This project handles the import of product data into Shopify using a structured approach. The goal is to ensure valid products are available in Shopify for sale while following best practices in software development.

## Table of Contents
- [Project Overview](#project-overview)
- [Architecture](#architecture)
- [Technologies Used](#technologies-used)
- [Setup and Installation](#setup-and-installation)
- [Usage](#usage)
- [Testing](#testing)
- [Logging and Monitoring](#logging-and-monitoring)
- [References](#references)

## Project Overview

The task involves importing a file containing product data into Shopify. All data from the file should be processed and imported into Shopify, ensuring that only valid products are imported.

Key requirements:
- Implement a layered architecture for the import process.
- Ensure that the products being imported are valid and ready for sale on Shopify.
- Use Test-Driven Development (TDD) to implement the solution.
- Verify test accuracy with Mutation Testing using Infection.
- Data should be sent to Shopify via GraphQL.
- Use Elasticsearch and Kibana for logging.

## Architecture

The solution follows a layered architecture with the following steps:

1. **Console Command**:
    - A command is used to initiate the process and read the file.

2. **File Processing**:
    - The file is read, and each product is mapped to a Data Transfer Object (DTO).

3. **DTO Processing**:
    - Each DTO is sent to a RabbitMQ queue for further processing.

4. **RabbitMQ Handling**:
    - The queue is consumed, and each DTO is prepared for sending to Shopify.

5. **GraphQL Communication**:
    - The product data is sent to Shopify using the GraphQL Admin API.

6. **Logging**:
    - Logs are generated throughout the process to track data flow and responses from Shopify.

![Architecture Diagram](assets/image.png)

## Technologies Used

- **Programming Language**: PHP
- **Framework**: Symfony (or other preferred PHP framework)
- **Messaging Queue**: RabbitMQ
- **Logging**: Elasticsearch, Kibana
- **Testing**: PHPUnit, Infection (Mutation Testing)
- **API Communication**: GraphQL
- **Monitoring and Analysis**: Kibana, Logz.io

## Setup and Installation

### Prerequisites

- PHP >= 7.4
- Composer
- RabbitMQ server
- Elasticsearch and Kibana
- Infection for Mutation Testing

### Installation Steps

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/shopify-import.git
