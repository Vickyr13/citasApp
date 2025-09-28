<?php
require_once 'config.php';

$database = new Database();
$conn = $database->getConnection();

// Procesar acciones del formulario
$accion = $_GET['accion'] ?? 'listar';
$mensaje = '';
$tipo_mensaje = '';

// CREATE - Crear nueva cita
if ($_POST['accion'] ?? '' == 'crear') {
    try {
        // Primero, verificar si el cliente ya existe o crearlo
        $cliente_existente = null;
        if (!empty($_POST['cliente_id']) && $_POST['cliente_id'] != 'nuevo') {
            $cliente_existente = $_POST['cliente_id'];
        } else {
            // Crear nuevo cliente
            $stmt_cliente = $conn->prepare("
                INSERT INTO clientes (nombre, apellido, telefono, email) 
                VALUES (?, ?, ?, ?)
            ");
            $nombre_completo = explode(' ', $_POST['nombre_cliente'], 2);
            $nombre = $nombre_completo[0];
            $apellido = $nombre_completo[1] ?? '';
            
            $stmt_cliente->execute([
                $nombre,
                $apellido,
                $_POST['telefono'],
                $_POST['email'] ?? ''
            ]);
            $cliente_existente = $conn->lastInsertId();
        }
        
        $stmt = $conn->prepare("
            INSERT INTO citas (cliente_id, servicio_id, fecha_cita, hora_cita, estado, observaciones) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $cliente_existente,
            $_POST['servicio_id'],
            $_POST['fecha_cita'],
            $_POST['hora_cita'],
            $_POST['estado'] ?? 'pendiente',
            $_POST['observaciones'] ?? ''
        ]);
        $mensaje = "Cita creada exitosamente";
        $tipo_mensaje = "success";
    } catch(PDOException $e) {
        $mensaje = "Error al crear la cita: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// UPDATE - Actualizar cita existente
if ($_POST['accion'] ?? '' == 'actualizar') {
    try {
        $stmt = $conn->prepare("
            UPDATE citas 
            SET cliente_id = ?, servicio_id = ?, fecha_cita = ?, hora_cita = ?, estado = ?, observaciones = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['cliente_id'],
            $_POST['servicio_id'],
            $_POST['fecha_cita'],
            $_POST['hora_cita'],
            $_POST['estado'],
            $_POST['observaciones'] ?? '',
            $_POST['id']
        ]);
        $mensaje = "Cita actualizada exitosamente";
        $tipo_mensaje = "success";
    } catch(PDOException $e) {
        $mensaje = "Error al actualizar la cita: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// DELETE - Eliminar cita
if ($accion == 'eliminar' && isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM citas WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $mensaje = "Cita eliminada exitosamente";
        $tipo_mensaje = "success";
    } catch(PDOException $e) {
        $mensaje = "Error al eliminar la cita: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// READ - Obtener citas con información de cliente y servicio
$stmt_citas = $conn->prepare("
    SELECT c.*, 
           cl.nombre as cliente_nombre, cl.apellido as cliente_apellido, cl.telefono,
           s.nombre as servicio_nombre, s.duracion, s.precio
    FROM citas c
    JOIN clientes cl ON c.cliente_id = cl.id
    JOIN servicios s ON c.servicio_id = s.id
    ORDER BY c.fecha_cita DESC, c.hora_cita DESC
");
$stmt_citas->execute();
$citas = $stmt_citas->fetchAll(PDO::FETCH_ASSOC);

// Obtener datos para los formularios
$stmt_clientes = $conn->prepare("SELECT * FROM clientes ORDER BY nombre, apellido");
$stmt_clientes->execute();
$clientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);

$stmt_servicios = $conn->prepare("SELECT * FROM servicios WHERE estado = 'activo' ORDER BY nombre");
$stmt_servicios->execute();
$servicios = $stmt_servicios->fetchAll(PDO::FETCH_ASSOC);

// Obtener cita para editar
$cita_editar = null;
if ($accion == 'editar' && isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM citas WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $cita_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Citas - Peluquería</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(45deg, #ff6b6b, #feca57);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .content {
            padding: 30px;
        }
        
        .mensaje {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: bold;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 5px solid #ff6b6b;
        }
        
        .form-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5em;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #ff6b6b;
            box-shadow: 0 0 5px rgba(255, 107, 107, 0.3);
        }
        
        .btn {
            background: linear-gradient(45deg, #ff6b6b, #feca57);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-danger {
            background: linear-gradient(45deg, #ff4757, #ff3742);
        }
        
        .btn-secondary {
            background: linear-gradient(45deg, #57606f, #2f3542);
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        th {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: bold;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        tr:hover {
            background-color: #e3f2fd;
            transition: background-color 0.2s;
        }
        
        .estado {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .estado-pendiente { background: #fff3cd; color: #856404; }
        .estado-confirmada { background: #d1ecf1; color: #0c5460; }
        .estado-completada { background: #d4edda; color: #155724; }
        .estado-cancelada { background: #f8d7da; color: #721c24; }
        
        .acciones {
            white-space: nowrap;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            margin: 2px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            
            .form-group {
                min-width: 100%;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💇‍♀️ Gestión de Citas</h1>
            <p>Sistema de reservas para peluquería</p>
        </div>
        
        <div class="content">
            <?php if ($mensaje): ?>
                <div class="mensaje <?php echo $tipo_mensaje; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-section">
                <h2><?php echo $cita_editar ? 'Editar Cita' : 'Registrar Nueva Cita'; ?></h2>
                
                <form method="POST" action="">
                    <input type="hidden" name="accion" value="<?php echo $cita_editar ? 'actualizar' : 'crear'; ?>">
                    <?php if ($cita_editar): ?>
                        <input type="hidden" name="id" value="<?php echo $cita_editar['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cliente_id">Cliente:</label>
                            <select name="cliente_id" id="cliente_id" required onchange="toggleClienteNuevo()">
                                <option value="">Seleccionar cliente</option>
                                <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?php echo $cliente['id']; ?>"
                                            <?php echo ($cita_editar && $cita_editar['cliente_id'] == $cliente['id']) ? 'selected' : ''; ?>>
                                        <?php echo $cliente['nombre'] . ' ' . $cliente['apellido'] . ' - ' . $cliente['telefono']; ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="nuevo" <?php echo (!$cita_editar) ? '' : ''; ?>>+ Nuevo Cliente</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="servicio_id">Servicio:</label>
                            <select name="servicio_id" id="servicio_id" required>
                                <option value="">Seleccionar servicio</option>
                                <?php foreach ($servicios as $servicio): ?>
                                    <option value="<?php echo $servicio['id']; ?>"
                                            <?php echo ($cita_editar && $cita_editar['servicio_id'] == $servicio['id']) ? 'selected' : ''; ?>>
                                        <?php echo $servicio['nombre'] . ' - ' . formatearPrecio($servicio['precio']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Campos para cliente nuevo (ocultos inicialmente) -->
                    <div id="cliente-nuevo-fields" style="display: none;">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombre_cliente">Nombre Completo:</label>
                                <input type="text" name="nombre_cliente" id="nombre_cliente" 
                                       placeholder="Ej: María González">
                            </div>
                            
                            <div class="form-group">
                                <label for="telefono">Teléfono: *</label>
                                <input type="tel" name="telefono" id="telefono" 
                                       placeholder="Ej: +503-1234-5678">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email (opcional):</label>
                                <input type="email" name="email" id="email" 
                                       placeholder="ejemplo@correo.com">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha_cita">Fecha:</label>
                            <input type="date" name="fecha_cita" id="fecha_cita" required
                                   value="<?php echo $cita_editar ? $cita_editar['fecha_cita'] : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="hora_cita">Hora:</label>
                            <input type="time" name="hora_cita" id="hora_cita" required
                                   value="<?php echo $cita_editar ? $cita_editar['hora_cita'] : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="estado">Estado:</label>
                            <select name="estado" id="estado">
                                <option value="pendiente" <?php echo (!$cita_editar || $cita_editar['estado'] == 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="confirmada" <?php echo ($cita_editar && $cita_editar['estado'] == 'confirmada') ? 'selected' : ''; ?>>Confirmada</option>
                                <option value="completada" <?php echo ($cita_editar && $cita_editar['estado'] == 'completada') ? 'selected' : ''; ?>>Completada</option>
                                <option value="cancelada" <?php echo ($cita_editar && $cita_editar['estado'] == 'cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="observaciones">Observaciones:</label>
                            <textarea name="observaciones" id="observaciones" rows="3"><?php echo $cita_editar ? htmlspecialchars($cita_editar['observaciones']) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">
                        <?php echo $cita_editar ? '📝 Actualizar Cita' : '💾 Registrar Cita'; ?>
                    </button>
                    
                    <?php if ($cita_editar): ?>
                        <a href="index.php" class="btn btn-secondary">❌ Cancelar</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="form-section">
                <h2>📋 Lista de Citas Registradas</h2>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Servicio</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Estado</th>
                                <th>Precio</th>
                                <th>Teléfono</th>
                                <th>Observaciones</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($citas)): ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 30px; color: #666;">
                                        No hay citas registradas
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($citas as $cita): ?>
                                    <tr>
                                        <td><?php echo $cita['id']; ?></td>
                                        <td>
                                            <strong><?php echo $cita['cliente_nombre'] . ' ' . $cita['cliente_apellido']; ?></strong>
                                        </td>
                                        <td><?php echo $cita['servicio_nombre']; ?></td>
                                        <td><?php echo formatearFecha($cita['fecha_cita']); ?></td>
                                        <td><?php echo formatearHora($cita['hora_cita']); ?></td>
                                        <td>
                                            <span class="estado estado-<?php echo $cita['estado']; ?>">
                                                <?php echo ucfirst($cita['estado']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatearPrecio($cita['precio']); ?></td>
                                        <td><?php echo $cita['telefono']; ?></td>
                                        <td><?php echo htmlspecialchars(substr($cita['observaciones'], 0, 50)) . (strlen($cita['observaciones']) > 50 ? '...' : ''); ?></td>
                                        <td class="acciones">
                                            <a href="?accion=editar&id=<?php echo $cita['id']; ?>" class="btn btn-small">✏️</a>
                                            <a href="?accion=eliminar&id=<?php echo $cita['id']; ?>" 
                                               class="btn btn-small btn-danger"
                                               onclick="return confirm('¿Estás seguro de eliminar esta cita?')">🗑️</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Establecer fecha mínima como hoy
        document.getElementById('fecha_cita').min = new Date().toISOString().split('T')[0];
        
        // Función para mostrar/ocultar campos de cliente nuevo
        function toggleClienteNuevo() {
            const clienteSelect = document.getElementById('cliente_id');
            const camposNuevo = document.getElementById('cliente-nuevo-fields');
            const telefonoField = document.getElementById('telefono');
            const nombreField = document.getElementById('nombre_cliente');
            
            if (clienteSelect.value === 'nuevo') {
                camposNuevo.style.display = 'block';
                telefonoField.required = true;
                nombreField.required = true;
            } else {
                camposNuevo.style.display = 'none';
                telefonoField.required = false;
                nombreField.required = false;
            }
        }
        
        // Validación del formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            const clienteSelect = document.getElementById('cliente_id');
            const nombreCliente = document.getElementById('nombre_cliente');
            const telefono = document.getElementById('telefono');
            
            if (clienteSelect.value === 'nuevo') {
                if (!nombreCliente.value.trim()) {
                    e.preventDefault();
                    alert('Por favor, ingresa el nombre completo del cliente');
                    nombreCliente.focus();
                    return;
                }
                
                if (!telefono.value.trim()) {
                    e.preventDefault();
                    alert('Por favor, ingresa el teléfono del cliente');
                    telefono.focus();
                    return;
                }
            }
        });
        
        // Auto-ocultar mensajes después de 5 segundos
        setTimeout(function() {
            const mensaje = document.querySelector('.mensaje');
            if (mensaje) {
                mensaje.style.opacity = '0';
                mensaje.style.transition = 'opacity 0.5s';
                setTimeout(() => mensaje.remove(), 500);
            }
        }, 5000);
    </script>
</body>
</html>