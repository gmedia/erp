# Employee Data Generator Command

## Overview
This command generates realistic dummy employee data for testing and development purposes.

## Usage

### Basic Usage
```bash
# Generate 1 employee (default)
./vendor/bin/sail artisan employee:create

# Generate multiple employees
./vendor/bin/sail artisan employee:create 10

# Generate 50 employees
./vendor/bin/sail artisan employee:create 50
```

### Features
- ✅ **Realistic Data**: Uses Faker to generate realistic names, emails, and job details
- ✅ **Unique Emails**: Ensures all email addresses are unique using database constraints
- ✅ **Progress Bar**: Shows real-time progress during generation
- ✅ **Error Handling**: Handles edge cases gracefully
- ✅ **Department Mapping**: Realistic department and position combinations
- ✅ **Salary Ranges**: Position-based realistic salary ranges
- ✅ **Hire Dates**: Random dates within the last 5 years
- ✅ **Transaction Safety**: Uses database transactions for data integrity

### Data Structure
Each employee record includes:
- **name**: Full name (realistic format)
- **email**: Unique email address
- **phone**: Optional phone number
- **department**: One of 8 realistic departments
- **position**: Position-specific job titles
- **salary**: Realistic salary based on position (USD)
- **hire_date**: Date within last 5 years

### Departments & Positions
- **Engineering**: Software Engineer, Senior Software Engineer, Tech Lead, etc.
- **Sales**: Sales Representative, Sales Manager, Account Executive, etc.
- **Marketing**: Marketing Coordinator, Marketing Manager, Content Writer, etc.
- **HR**: HR Coordinator, HR Manager, Recruiter, HR Director
- **Finance**: Accountant, Financial Analyst, Finance Manager, CFO
- **Operations**: Operations Manager, Project Coordinator, Office Manager, etc.
- **Customer Support**: Support Agent, Support Manager, Customer Success Manager
- **Product**: Product Manager, Product Owner, UX Designer, UI Designer

### Error Handling
- **Invalid count**: Shows error for non-positive integers
- **Duplicate emails**: Automatically generates unique emails
- **Database errors**: Provides clear error messages
- **Memory limits**: Handles large batches efficiently

### Examples
```bash
# Quick test with 3 employees
./vendor/bin/sail artisan employee:create 3

# Large dataset for performance testing
./vendor/bin/sail artisan employee:create 1000

# Default single employee
./vendor/bin/sail artisan employee:create