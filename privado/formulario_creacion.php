<!-- El atributo enctype="multipart/form-data" es obligatorio para que PHP pueda recibir archivos binarios (.jpg) -->
<form action="procesar_producto.php" method="POST" enctype="multipart/form-data" class="formulario-producto">
    
    <!-- Campos ocultos de control interno -->
    <input type="hidden" name="accion" value="<?php echo $producto_a_editar ? 'editar' : 'crear'; ?>">
    <?php if ($producto_a_editar): ?>
        <input type="hidden" name="id" value="<?php echo $producto_a_editar['id']; ?>">
    <?php endif; ?>

    <div class="grupo-campo">
        <label for="nombre">Nombre Comercial del Perfume</label>
        <input type="text" id="nombre" name="nombre" required value="<?php echo $producto_a_editar['nombre'] ?? ''; ?>" placeholder="Ej: Invictus Paco Rabanne 100ml">
    </div>

    <div class="grupo-campo">
        <label for="descripcion">Descripción de Notas Olfativas</label>
        <textarea id="descripcion" name="descripcion" required rows="4" placeholder="Notas de salida, corazón y fondo..."><?php echo $producto_a_editar['descripcion'] ?? ''; ?></textarea>
    </div>

    <div class="fila-doble">
        <div class="grupo-campo">
            <label for="precio">Precio de Venta ($)</label>
            <input type="number" id="precio" name="precio" step="0.01" required value="<?php echo $producto_a_editar['precio'] ?? ''; ?>" placeholder="0.00">
        </div>
        
        <div class="grupo-campo">
            <label for="stock">Unidades Disponibles en Bodega</label>
            <input type="number" id="stock" name="stock" required value="<?php echo $producto_a_editar['stock'] ?? ''; ?>" placeholder="0">
        </div>
    </div>

    <div class="grupo-campo">
        <label for="foto">Fotografía Particular del Producto</label>
        <input type="file" id="foto" name="foto" accept="image/jpeg, image/png, image/webp" <?php echo $producto_a_editar ? '' : 'required'; ?>>
        
        <?php if ($producto_a_editar && $producto_a_editar['imagen']): ?>
            <p class="ayuda-foto">Foto actual registrada: <strong><?php echo $producto_a_editar['imagen']; ?></strong><br>
            (Deja este campo vacío si no deseas modificar la imagen actual)</p>
        <?php endif; ?>
    </div>

    <div class="acciones-formulario">
        <button type="submit" class="btn-guardar">
            <?php echo $producto_a_editar ? 'Actualizar Cambios' : 'Ingresar Catálogo'; ?>
        </button>
    </div>
</form>