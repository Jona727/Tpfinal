if (typeof OfflineManager === 'undefined') {
    const DB_NAME = 'SolufeedDB';
    const DB_VERSION = 1;
    const STORE_NAME = 'offline_queue';

    window.OfflineManager = {
        db: null,

        /**
         * Inicializa la base de datos IndexedDB
         */
        initDB: function () {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open(DB_NAME, DB_VERSION);

                request.onupgradeneeded = (event) => {
                    const db = event.target.result;
                    if (!db.objectStoreNames.contains(STORE_NAME)) {
                        db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
                    }
                };

                request.onsuccess = (event) => {
                    this.db = event.target.result;
                    console.log('📦 [DB] IndexedDB inicializada con éxito');
                    this.updateUIStatus();
                    resolve(this.db);
                };

                request.onerror = (event) => {
                    console.error('❌ [DB] Error al abrir IndexedDB:', event.target.error);
                    reject(event.target.error);
                };
            });
        },

        /**
         * Guarda una operación en la base de datos local
         */
        saveToQueue: async function (endpoint, data, tipo) {
            if (!this.db) await this.initDB();

            const operation = {
                endpoint,
                data,
                tipo,
                timestamp: new Date().toISOString()
            };

            const transaction = this.db.transaction([STORE_NAME], 'readwrite');
            const store = transaction.objectStore(STORE_NAME);

            return new Promise((resolve, reject) => {
                const request = store.add(operation);
                request.onsuccess = () => {
                    console.warn('📡 [Offline] Operación guardada en IndexedDB', operation);
                    if (typeof showToast === 'function') {
                        showToast(`Sin conexión: Registro de ${tipo} guardado localmente.`, 'warning');
                    }
                    this.updateUIStatus();
                    resolve();
                };
                request.onerror = (e) => reject(e.target.error);
            });
        },

        /**
         * Recupera todas las operaciones pendientes
         */
        getPendingItems: async function () {
            if (!this.db) await this.initDB();

            return new Promise((resolve, reject) => {
                const transaction = this.db.transaction([STORE_NAME], 'readonly');
                const store = transaction.objectStore(STORE_NAME);
                const request = store.getAll();

                request.onsuccess = () => resolve(request.result);
                request.onerror = (e) => reject(e.target.error);
            });
        },

        /**
         * Elimina una operación procesada
         */
        removeItem: async function (id) {
            const transaction = this.db.transaction([STORE_NAME], 'readwrite');
            const store = transaction.objectStore(STORE_NAME);
            return new Promise((resolve) => {
                const request = store.delete(id);
                request.onsuccess = () => resolve();
            });
        },

        /**
         * Sincronización robusta v2.5
         */
        sync: async function () {
            if (!navigator.onLine) return;

            const pendingItems = await this.getPendingItems();
            // Filtrar: solo registros reales que tengan una URL válida (endpoint)
            const realData = pendingItems.filter(item => {
                return item.id !== 'current_session' &&
                    item.endpoint &&
                    item.endpoint !== 'undefined' &&
                    typeof item.endpoint === 'string';
            });

            if (realData.length === 0) return;

            console.log(`🔃 [Sync] Detectados ${realData.length} registros para subir.`);
            this.toggleSyncUI(true);

            for (const item of realData) {
                try {
                    console.log(`📤 Sincronizando ${item.tipo} con: ${item.endpoint}`);
                    const response = await fetch(item.endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams(item.data)
                    });

                    if (response.ok) {
                        await this.removeItem(item.id);
                        console.log(`✅ [Sync] Registro #${item.id} ok.`);
                    } else {
                        console.error(`⚠️ [Sync] Error servidor en #${item.id}:`, response.status);
                        // Si es un error fatal de la petición, podríamos borrarlo para no bloquear la cola
                        if (response.status === 404) await this.removeItem(item.id);
                    }
                } catch (error) {
                    console.error(`❌ [Sync] Error de red en #${item.id}:`, error);
                    break; // Si falla la red del todo, paramos la cola
                }
            }

            this.toggleSyncUI(false);
            this.updateUIStatus();

            const remaining = (await this.getPendingItems()).filter(i => i.id !== 'current_session');
            if (remaining.length === 0) {
                if (typeof showToast === 'function') showToast('Sincronización finalizada.', 'success');

                // Recargar si estamos en páginas de consulta para ver cambios
                const path = window.location.pathname;
                if (path.includes('index.php') || path.includes('historial') || path.includes('consultar')) {
                    setTimeout(() => window.location.reload(), 1500);
                }
            }
        },

        /**
         * Actualiza el contador visual en la interfaz
         */
        updateUIStatus: async function () {
            const allItems = await this.getPendingItems();
            // Filtrar solo registros reales (excluir la sesión técnica)
            const items = allItems.filter(i => i.id !== 'current_session');

            const statusDiv = document.getElementById('connection-status');
            if (!statusDiv) return;

            if (items.length > 0) {
                statusDiv.innerHTML = `📡 Tienes <b>${items.length}</b> registros pendientes de sincronizar.`;
                statusDiv.className = 'card alerta-offline';
                statusDiv.style.display = 'block';
                statusDiv.style.background = '#fff9db';
                statusDiv.style.borderLeft = '5px solid #fab005';
                statusDiv.style.padding = '1rem';
                statusDiv.style.marginBottom = '1rem';
            } else {
                statusDiv.style.display = 'none';
            }
        },

        toggleSyncUI: function (show) {
            let overlay = document.getElementById('sync-progress-overlay');
            if (!overlay && show) {
                overlay = document.createElement('div');
                overlay.id = 'sync-progress-overlay';
                overlay.style = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(44,85,48,0.85); color:white; z-index:10000; display:flex; flex-direction:column; justify-content:center; align-items:center; font-family:Outfit, sans-serif;';
                overlay.innerHTML = `
                    <div style="font-size:3rem; margin-bottom:1rem; animation: rotate 2s linear infinite;">🔃</div>
                    <h2 style="margin:0;">Sincronizando con Solufeed...</h2>
                    <p>Por favor, no cierres el navegador.</p>
                    <style>@keyframes rotate { from {transform:rotate(0deg);} to {transform:rotate(360deg);} }</style>
                `;
                document.body.appendChild(overlay);
            }
            if (overlay) overlay.style.display = show ? 'flex' : 'none';
        }
    };

    // Inicialización y Listeners
    window.addEventListener('online', () => OfflineManager.sync());
    window.addEventListener('load', () => {
        OfflineManager.initDB().then(() => {
            if (navigator.onLine) OfflineManager.sync();
        });
    });
}
