-- Entity 1: vehicles
CREATE TABLE IF NOT EXISTS vehicles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    vin VARCHAR(20) UNIQUE,
    plate VARCHAR(15),
    fuel_capacity NUMERIC(4, 2),
    initial_odometer INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- Entity 2: fuel_logs
CREATE TABLE IF NOT EXISTS fuel_logs (
    id SERIAL PRIMARY KEY,
    vehicle_id INT NOT NULL REFERENCES vehicles(id) ON DELETE CASCADE,
    odometer INT NOT NULL CHECK (odometer >= 0),
    fuel_volume NUMERIC(6, 3) NOT NULL CHECK (fuel_volume > 0),
    price_total NUMERIC(6, 2),
    fuel_grade VARCHAR(10),
    is_full BOOLEAN DEFAULT TRUE,
    missed_previous BOOLEAN DEFAULT FALSE,
    station_location VARCHAR(100),
    notes TEXT,
    log_date TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_logs_vehicle_date ON fuel_logs(vehicle_id, log_date DESC);

-- View: odometer rollover handling + "distance_traveled"
CREATE OR REPLACE VIEW view_fuel_efficiency AS
WITH OrderedLogs AS (
    SELECT
        id,
        vehicle_id,
        log_date,
        odometer,
        fuel_volume,
        is_full,
        missed_previous,
        LAG(odometer) OVER (PARTITION BY vehicle_id ORDER BY log_date ASC) AS prev_odometer
    FROM fuel_logs
)
SELECT
    id,
    vehicle_id,
    log_date,
    odometer,
    fuel_volume,
    prev_odometer,
    CASE
        WHEN prev_odometer IS NULL THEN 0
        WHEN missed_previous = TRUE THEN NULL
        WHEN odometer < prev_odometer THEN (odometer + 100000) - prev_odometer
        ELSE odometer - prev_odometer
    END AS distance_traveled
FROM OrderedLogs;