# OMC - Developer Challenge
## Background
Alina is the owner of a new commercial tower, named A-Tower.
Tower is mainly composed of a base skeleton with reflective glass windows, each window is
equipped with a temperature
sensor.
Your goal is to design and implement a system which helps Alina with monitoring faulty
sensors and keeping track of the
average hourly temperature for each face of the building (north, east, south, west).
## Specification
Sensors may be added or removed at any time. 
Sensor without sampling data in the past 24
hours is considered as removed.

The expected number of sensors is: 10000

Each sensor has a temperature sampling rate of 1 sample per second.
Information sent by sensors is a json payload composed of the following fields:
- timestamp: unix timestamp (integer)
- id: sensor numeric id (integer)
- face: south, east, north, west - indicates the position the sensor is facing. (enum)
- temperature value: degrees (Celsius) (double)

## Requirements
### Sensor malfunction notification
Definition: malfunctioning sensor is a sensor with a deviate of more than 20% of the rest of
the sensors facing the same side.
NOTE: A log based notification will suffice for this challenge (you are not required to alert
with an email or SMS).
### Aggregated hourly temperature reading
System should aggregate and average data on an hourly basis, aggregated data includes to
overall temperature for each
face of the tower.
### Reporting

- On demand report, summarize aggregated hourly temperatures for the past week.
- On demand report, list all malfunctioning sensors (id, average value)

## Instructions
- User interface (reports) should be minimal, you are not required to implement anything but
  basic interface.
- Implement on top of web technology, all requests are http based.
- You may assume security is implemented at the infrastructure level (you are not required to
  implement security measures)
- User management, authentication and authorization is not required.
- Software stack is choosen by you, based on your experience and architecture.
- Use php slim framework in your solution (https://www.slimframework.com)
- Create a dockerfile for the solution