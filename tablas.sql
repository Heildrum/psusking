CREATE TABLE IF NOT EXISTS administradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    secreto_2fa VARCHAR(100) NULL, -- Llave para enlazar el código de tu celular
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================================
-- 2. TABLA: PRODUCTOS (Tus perfumes y sus fotos particulares)
-- =============================================================
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    descripcion TEXT NOT NULL,
    precio DECIMAL(10, 2) NOT NULL, -- Permite montos grandes y decimales exactos
    imagen VARCHAR(255) NOT NULL,   -- Aquí se guarda el texto (ej: 'grenouille_essence.jpg')
    stock INT NOT NULL DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================================
-- 3. TABLA: PEDIDOS (Ventas generales que se validan con el banco)
-- =============================================================
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    nombre_cliente VARCHAR(150) NOT NULL,
    correo_cliente VARCHAR(150) NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    estado_pago ENUM('Pendiente', 'Aprobado', 'Rechazado') DEFAULT 'Pendiente',
    token_pago VARCHAR(255) NULL -- Código único que nos da la API del banco
) ENGINE=InnoDB;

-- =============================================================
-- 4. TABLA: DETALLE_PEDIDOS (El desglose de qué perfumes compraron)
-- =============================================================
CREATE TABLE IF NOT EXISTS detalle_pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10, 2) NOT NULL,
    
    -- Relaciones de integridad (Llaves foráneas)
    CONSTRAINT fk_detalle_pedido FOREIGN KEY (pedido_id) 
        REFERENCES pedidos(id) ON DELETE CASCADE,
        
    CONSTRAINT fk_detalle_producto FOREIGN KEY (producto_id) 
        REFERENCES productos(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE TABLE `Pasarela` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `client_id` VARCHAR(100) NOT NULL,
    `client_secret` VARCHAR(100) NOT NULL,
    `access_token` TEXT NOT NULL,
    `refresh_token` VARCHAR(150) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `payments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` BIGINT UNSIGNED NOT NULL, -- Clave foránea a tu tabla 'orders' o 'pedidos'
    `gateway_payment_id` VARCHAR(100) NOT NULL, -- El ID que te entrega Mercado Pago
    `amount` DECIMAL(10, 2) NOT NULL, -- El monto total cobrado (ej: 15500.50)
    `status` VARCHAR(50) NOT NULL, -- 'pending', 'approved', 'rejected', 'refunded'
    `payment_method` VARCHAR(50) NULL, -- 'credit_card', 'debit_card', 'ticket', etc.
    `payload` JSON NULL, -- Guarda la respuesta completa de la API por si necesitas auditar errores
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Relación con tu tabla existente (Ajusta 'orders' por el nombre real de tu tabla de pedidos)
    CONSTRAINT `fk_payments_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;