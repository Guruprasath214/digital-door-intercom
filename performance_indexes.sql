-- Performance optimization indexes for Supabase PostgreSQL

-- Indexes for faster queries on dashboard and common operations

-- Blocks table
CREATE INDEX IF NOT EXISTS idx_blocks_name ON blocks(name);

-- Floors table
CREATE INDEX IF NOT EXISTS idx_floors_block_id ON floors(block_id);
CREATE INDEX IF NOT EXISTS idx_floors_floor_no ON floors(floor_no);

-- Flats table
CREATE INDEX IF NOT EXISTS idx_flats_block_id ON flats(block_id);
CREATE INDEX IF NOT EXISTS idx_flats_number ON flats(number);
CREATE INDEX IF NOT EXISTS idx_flats_occupied ON flats(occupied);

-- Users table
CREATE INDEX IF NOT EXISTS idx_users_flat_id ON users(flat_id);
CREATE INDEX IF NOT EXISTS idx_users_is_primary ON users(is_primary);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_mobile ON users(mobile);

-- Visitors table
CREATE INDEX IF NOT EXISTS idx_visitors_flat_id ON visitors(flat_id);
CREATE INDEX IF NOT EXISTS idx_visitors_check_in ON visitors(check_in);
CREATE INDEX IF NOT EXISTS idx_visitors_check_in_date ON visitors(DATE(check_in));
CREATE INDEX IF NOT EXISTS idx_visitors_mobile ON visitors(mobile);

-- Appointments table
CREATE INDEX IF NOT EXISTS idx_appointments_user_id ON appointments(user_id);
CREATE INDEX IF NOT EXISTS idx_appointments_visitor_id ON appointments(visitor_id);
CREATE INDEX IF NOT EXISTS idx_appointments_appointment_time ON appointments(appointment_time);
CREATE INDEX IF NOT EXISTS idx_appointments_status ON appointments(status);
CREATE INDEX IF NOT EXISTS idx_appointments_appointment_date ON appointments(DATE(appointment_time));

-- Notifications table
CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_notifications_is_read ON notifications(is_read);
CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON notifications(created_at);