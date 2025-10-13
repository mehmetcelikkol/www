-- SCADA1 veritabanı için örnek tablo yapısı
-- Bu SQL'i phpMyAdmin'de çalıştırarak test tablosu oluşturabilirsiniz

CREATE DATABASE IF NOT EXISTS scada1;
USE scada1;

-- Örnek SCADA verileri tablosu
CREATE TABLE IF NOT EXISTS sensor_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sensor_id VARCHAR(50) NOT NULL,
    sensor_name VARCHAR(100) NOT NULL,
    temperature DECIMAL(5,2),
    pressure DECIMAL(8,2),
    humidity DECIMAL(5,2),
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Örnek alarm verileri tablosu
CREATE TABLE IF NOT EXISTS alarm_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alarm_type VARCHAR(50) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
    message TEXT,
    sensor_id VARCHAR(50),
    acknowledged BOOLEAN DEFAULT FALSE,
    acknowledged_by VARCHAR(100),
    acknowledged_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Örnek sistem durumu tablosu
CREATE TABLE IF NOT EXISTS system_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    system_name VARCHAR(100) NOT NULL,
    cpu_usage DECIMAL(5,2),
    memory_usage DECIMAL(5,2),
    disk_usage DECIMAL(5,2),
    network_status ENUM('online', 'offline', 'unstable') DEFAULT 'online',
    last_maintenance DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Örnek veri ekleme
INSERT INTO sensor_data (sensor_id, sensor_name, temperature, pressure, humidity, status, location) VALUES
('TEMP001', 'Sıcaklık Sensörü 1', 23.5, 1013.25, 45.2, 'active', 'Bölge A'),
('TEMP002', 'Sıcaklık Sensörü 2', 25.1, 1012.80, 48.7, 'active', 'Bölge B'),
('PRES001', 'Basınç Sensörü 1', 22.8, 1015.30, 42.1, 'active', 'Bölge A'),
('HUM001', 'Nem Sensörü 1', 24.2, 1014.10, 52.3, 'maintenance', 'Bölge C'),
('TEMP003', 'Sıcaklık Sensörü 3', 21.9, 1013.90, 39.8, 'active', 'Bölge D');

INSERT INTO alarm_logs (alarm_type, severity, message, sensor_id) VALUES
('Temperature High', 'high', 'Sıcaklık değeri normal aralığın üzerinde', 'TEMP002'),
('Sensor Offline', 'critical', 'Sensör bağlantısı kesildi', 'HUM001'),
('Pressure Low', 'medium', 'Basınç değeri düşük seviyede', 'PRES001'),
('System Maintenance', 'low', 'Rutin bakım zamanı geldi', 'TEMP003');

INSERT INTO system_status (system_name, cpu_usage, memory_usage, disk_usage, network_status, last_maintenance) VALUES
('SCADA Server 1', 45.2, 67.8, 32.1, 'online', '2024-07-15'),
('SCADA Server 2', 38.7, 72.3, 28.9, 'online', '2024-07-20'),
('Backup Server', 12.4, 35.6, 45.2, 'online', '2024-07-10');
