CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

DROP VIEW IF EXISTS v_fuel_log_corrected;
DROP TABLE IF EXISTS maintenance_schedules CASCADE;
DROP TABLE IF EXISTS service_entries CASCADE;
DROP TABLE IF EXISTS service_tasks CASCADE;
DROP TABLE IF EXISTS fuel_entries CASCADE;
DROP TABLE IF EXISTS vehicle_modifications CASCADE;
DROP TABLE IF EXISTS vehicles CASCADE;
DROP TYPE IF EXISTS system_of_measurement CASCADE;

CREATE TYPE system_of_measurement AS ENUM ('metric', 'imperial_us', 'imperial_uk');

-- 1. Vehicles
CREATE TABLE vehicles (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(255) NOT NULL,
    make VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    year INTEGER NOT NULL,
    vin VARCHAR(17),
    license_plate VARCHAR(20),
    fuel_capacity_liters NUMERIC(5, 2) NOT NULL DEFAULT 0,
    system_of_measurement system_of_measurement DEFAULT 'metric',
    odometer_correction_factor NUMERIC(6, 4) DEFAULT 1.0000,
    settings JSONB DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 2. Fuel Entries
CREATE TABLE fuel_entries (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    vehicle_id UUID NOT NULL REFERENCES vehicles(id) ON DELETE CASCADE,
    filled_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    odometer_reading NUMERIC(10, 1) NOT NULL,
    fuel_volume_liters NUMERIC(8, 3) NOT NULL,
    total_cost NUMERIC(10, 2),
    currency_code CHAR(3) DEFAULT 'USD',
    is_full BOOLEAN DEFAULT TRUE,
    missed_fill BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_fuel_entries_vehicle_date ON fuel_entries(vehicle_id, filled_at DESC);

-- 3. Service Tasks
CREATE TABLE service_tasks (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 4. Maintenance Schedules
CREATE TABLE maintenance_schedules (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    vehicle_id UUID NOT NULL REFERENCES vehicles(id) ON DELETE CASCADE,
    service_task_id UUID NOT NULL REFERENCES service_tasks(id) ON DELETE RESTRICT,
    interval_distance_km INTEGER,
    interval_months INTEGER,
    last_performed_at TIMESTAMPTZ,
    last_performed_odometer NUMERIC(10, 1),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 5. Service Entries
CREATE TABLE service_entries (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    vehicle_id UUID NOT NULL REFERENCES vehicles(id) ON DELETE CASCADE,
    service_task_id UUID REFERENCES service_tasks(id),
    performed_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    odometer_at_service NUMERIC(10, 1) NOT NULL,
    cost NUMERIC(10, 2),
    currency_code CHAR(3) DEFAULT 'USD',
    meta JSONB DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 6. Vehicle Modifications
CREATE TABLE vehicle_modifications (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    vehicle_id UUID NOT NULL REFERENCES vehicles(id) ON DELETE CASCADE,
    effective_date TIMESTAMPTZ NOT NULL,
    modification_type VARCHAR(50),
    correction_factor NUMERIC(6, 4) NOT NULL,
    description TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 7. The Virtual Odometer View
CREATE OR REPLACE VIEW v_fuel_log_corrected AS
SELECT
    f.id,
    f.vehicle_id,
    f.filled_at,
    f.odometer_reading AS raw_dashboard_odometer,
    (f.odometer_reading * v.odometer_correction_factor) AS true_odometer,
    f.fuel_volume_liters,
    f.is_full,
    f.missed_fill,
    f.total_cost,
    v.system_of_measurement
FROM fuel_entries f
JOIN vehicles v ON f.vehicle_id = v.id;

-- Seed Data
INSERT INTO vehicles (id, name, make, model, year, fuel_capacity_liters, system_of_measurement)
VALUES ('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', 'Daily Rider', 'Honda', 'CB650R', 2021, 15.4, 'metric');

INSERT INTO service_tasks (id, name, description) 
VALUES ('c0ffee00-0000-0000-0000-000000000001', 'Oil Change', 'Routine 5k Service');

INSERT INTO maintenance_schedules (vehicle_id, service_task_id, interval_distance_km, last_performed_odometer, last_performed_at)
SELECT id, 'c0ffee00-0000-0000-0000-000000000001', 5000, 0, NOW()
FROM vehicles LIMIT 1;