# Tarifador - Call Accounting

## Description

**Tarifador** is an advanced **Call Accounting and Billing** module for FreePBX. It is designed to provide administrators and managers with detailed analysis and cost tracking of phone calls.

The module allows for precise cost calculation based on customizable rate decks and offers a comprehensive visual dashboard to monitor call traffic and expenses effectively.

## Key Features

* **Detailed Call Reports:** Access comprehensive Call Detail Records (CDR) with advanced filtering options (Date, Source, Destination, Status, and User). Supports consolidated views for transferred calls (LinkedID).
* **Cost Calculation:** Automatically calculates the cost of every answered call based on your pre-defined rates.
* **Statistics Dashboard:** Visual charts for real-time analysis, including:
    * Call Disposition Stats (Answered, Busy, Failed)
    * Top 50 Sources (Who is calling the most)
    * Top 50 Destinations (Most dialed numbers)
    * Hourly Call Distribution (Peak hour analysis)
* **Rate Management:** Create, edit, and manage rates based on **Dial Patterns**. You can define specific costs per minute for local, mobile, or international calls.
* **User & PIN Management:** Map Asterisk Account Codes (PINs) to real Usernames and Departments, making cost allocation and auditing much easier.

## Requirements

* FreePBX 17
* PHP 8.2

## Installation

1.  Download the latest version of the module from https://github.com/mrpbueno/tarifador/releases
2.  Upload the module via the **Module Admin** in FreePBX or install it manually.
3.  Enable the module and reload the configuration.

## License

This module is distributed under the **AGPLv3** license.

[FreePBX](http://www.freepbx.org/) is a registered trademark of [Sangoma Technologies Inc.](http://www.freepbx.org/copyright.html)
