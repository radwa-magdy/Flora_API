# Flora API
> A lightweight RESTful API for a flora e-commerce platform, built with pure PHP 8.2 and MySQL.

Flora API is a backend project focused on clean API architecture, database integration, containerized deployment, and framework-free PHP development.

---

## Features

* Product management system
* Shopping cart operations
* Checkout & order processing
* Admin dashboard management
* Clean RESTful API routing
* Structured JSON responses
* Dockerized environment
* Railway-ready deployment
* Protected configuration files using `.htaccess`
* Lightweight framework-free backend

---

## Tech Stack

| Layer            | Technology         |
| ---------------- | ------------------ |
| Language         | PHP 8.2            |
| Database         | MySQL              |
| Database Access  | PDO + MySQLi       |
| Containerization | Docker             |
| Deployment       | Railway            |
| Routing          | Apache `.htaccess` |

---

## Why This Project?

Flora API was built to practice backend development fundamentals without using heavy frameworks.

The project focuses on:

* RESTful API design
* MySQL database integration
* Docker containerization
* Clean URL routing
* Environment-based configuration
* JSON API responses


---

## Architecture Overview

The project uses a simple modular structure:

* **API Layer** → Handles requests and JSON responses
* **Database Layer** → MySQL with PDO
* **Routing Layer** → Clean REST endpoints using `.htaccess`
* **Deployment Layer** → Dockerized setup for easy deployment

The API is built with pure PHP to demonstrate core backend concepts and framework-free development.

---

## Project Structure

```bash
Flora_API/
├── api/                              # API endpoint files
│   ├── products.php
│   ├── cart.php
│   ├── checkout.php
│   ├── dashboard_api.php
│   ├── dashboard_functions.php
│   ├── productsDashboard_api.php
│   └── productsDashboard_functions.php
├── config/                           # Database configuration
│    ├── db.php
│   └── db-db.php
│
├── .htaccess                         # URL routing & security rules
├── Dockerfile                        # Docker container setup
└── README.md
```

---

## API Endpoints

Base URL:

```bash
https://floraapi-production-e891.up.railway.app
```

---

# Products

### Get All Products

```http
GET /api/products.php
```

### Response

```json
[
  {
    "product_id": 1,
    "product_name": "Pink Roses Bouquet",
    "price": "450.00",
    "stock": 12
  }
]
```

---

# Products Dashboard

### Get Products

```http
GET /api/productsDashboard_api.php/products
```

### Get Categories

```http
GET /api/productsDashboard_api.php/categories
```

---

### Add Product

```http
POST /api/productsDashboard_api.php/add
```

### Form Data

```text
product_name   : text
category_id    : number
collections    : text
price          : number
stock          : number
status         : text
description    : text
image_url      : file
```

---

### Edit Product

```http
POST /api/productsDashboard_api.php/edit?id=5
```

### JSON Body

```json
{
  "product_name": "White Tulips",
  "category_id": 2,
  "collections": "Spring Collection",
  "price": 320,
  "stock": 8,
  "status": "Active",
  "description": "Fresh tulip bouquet"
}
```

### Optional Form Data

```text
product_image : file
```

---

### Delete Product

```http
DELETE /api/productsDashboard_api.php/delete?id=5
```

---

# Overview Dashboard

### Get Dashboard Data

```http
GET /api/dashboard_api.php
```

---

### Update Order Status

```http
PUT /api/dashboard_api.php?order_id=5
```

### JSON Body

```json
{
  "status": "Delivered"
}
```

---

# Cart

### Get Cart

```http
GET /api/cart.php?customer_id=1
```

---

### Add Item to Cart

```http
POST /api/cart.php
```

### JSON Body

```json
{
  "customer_id": 1,
  "product_id": 17,
  "quantity": 2
}
```

---

### Update Cart Item

```http
PUT /api/cart.php
```

### JSON Body

```json
{
  "cart_id": 1,
  "quantity": 3
}
```

---

### Delete Cart Item

```http
DELETE /api/cart.php
```

### JSON Body

```json
{
  "cart_id": 1
}
```

---

# Checkout

### Create Order / Checkout

```http
POST /api/checkout.php
```

### JSON Body

```json
{
  "customer_id": 1,
  "shipping": {
    "first_name": "Radwa",
    "last_name": "Magdy",
    "street_address": "123 Flower St",
    "city": "Alexandria",
    "state": "Alex",
    "zip": "21500",
    "shipping_method": "Standard"
  },
  "payment": {
    "card_number": "**** **** **** 1234",
    "expiration_date": "12/28",
    "cvv": "***"
  }
}
```

---

## Example Response

```json
{
  "status": "success",
  "message": "Order placed successfully."
}
```

---

## Security Considerations

* Environment variables are used for database credentials
* PDO prepared statements help prevent SQL injection
* `.htaccess` protects sensitive configuration files
* Containerized deployment keeps environments consistent

---

## Project Goals

This project was created to improve practical backend development skills and demonstrate understanding of:

* REST API development
* Database-driven applications
* Docker workflows
* Cloud deployment
* Clean PHP project structure

---
