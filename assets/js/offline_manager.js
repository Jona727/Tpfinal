/**
 * Solufeed Offline Manager
 * Maneja el almacenamiento local y sincronización cuando vuelve la conexión.
 */

const OfflineManager = {
    // Clave para guardar en LocalStorage
    STORAGE_KEY: 'solufeed_offline_queue',

    /**
     * Guarda una operación en la cola local
     * @param {string} endpoint - URL a donde enviar los datos
     * @param {object} data - Datos del formulario
     * @param {string} tipo - 'alimentacion' o 'pesada'
     */
    saveToQueue: (endpoint, data, tipo) => {
        let queue = JSON.parse(localStorage.getItem(OfflineManager.STORAGE_KEY) || '[]');

        const operation = {
            id: Date.now(), // ID único
            endpoint: endpoint,
            data: data,
            tipo: tipo,
            timestamp: new Date().toISOString()
        };

        queue.push(operation);
        localStorage.setItem(OfflineManager.STORAGE_KEY, JSON.stringify(queue));

        console.warn('📡 Offline: Operación guardada localmente', operation);
        alert('🌐 Sin conexión: Datos guardados en el dispositivo. Se enviarán cuando recuperes la señal.');

        OfflineManager.updateUIStatus();
    },

    /**
     * Intenta sincronizar los datos pendientes
     */
    sync: async () => {
        if (!navigator.onLine) {
            console.log('📴 Aún offline, esperando...');
            return;
        }

        let queue = JSON.parse(localStorage.getItem(OfflineManager.STORAGE_KEY) || '[]');
        if (queue.length === 0) return;

        console.log(`🔃 Iniciando sincronización de ${queue.length} elementos...`);
        OfflineManager.showSyncIndicator(true);

        const newQueue = []; // Cola para los que fallen

        for (const op of queue) {
            try {
                const response = await fetch(op.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(op.data)
                });

                if (response.ok) {
                    console.log(`✅ Sincronizado: ${op.tipo} (#${op.id})`);
                } else {
                    console.error(`❌ Falló la sincronización: ${op.tipo} (#${op.id})`);
                    newQueue.push(op); // Mantener en cola si falla por error de servidor
                }
            } catch (error) {
                console.error(`❌ Error de red al sincronizar: ${op.tipo} (#${op.id})`);
                newQueue.push(op); // Mantener en cola si falla la red
            }
        }

        localStorage.setItem(OfflineManager.STORAGE_KEY, JSON.stringify(newQueue));
        OfflineManager.showSyncIndicator(false);
        OfflineManager.updateUIStatus();

        if (newQueue.length === 0) {
            alert('✅ Sincronización completada. Todos los datos han sido enviados al servidor.');
            window.location.reload(); // Recargar para actualizar contadores
        }
    },

    /**
     * Actualiza la interfaz visual del estado de conexión
     */
    updateUIStatus: () => {
        const queue = JSON.parse(localStorage.getItem(OfflineManager.STORAGE_KEY) || '[]');
        const statusDiv = document.getElementById('connection-status');

        if (!statusDiv) return; // Si no existe el elemento en el DOM

        if (queue.length > 0) {
            statusDiv.innerHTML = `⚠️ <b>${queue.length}</b> operaciones pendientes de subir.`;
            statusDiv.style.backgroundColor = '#fff3cd';
            statusDiv.style.color = '#856404';
            statusDiv.style.display = 'block';
        } else {
            statusDiv.style.display = 'none';
        }
    },

    /**
     * Muestra/Oculta overlay de "Sincronizando..."
     */
    showSyncIndicator: (show) => {
        let el = document.getElementById('sync-overlay');
        if (!el && show) {
            el = document.createElement('div');
            el.id = 'sync-overlay';
            el.style = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);color:white;display:flex;justify-content:center;align-items:center;z-index:9999;font-size:1.5em;';
            el.innerHTML = '🔃 Sincronizando datos...';
            document.body.appendChild(el);
        }

        if (el) el.style.display = show ? 'flex' : 'none';
    }
};

// Listeners globales
window.addEventListener('online', OfflineManager.sync);
window.addEventListener('load', () => {
    OfflineManager.updateUIStatus();
    // Intentar sync al cargar si hay conexión
    if (navigator.onLine) {
        OfflineManager.sync();
    }
});
