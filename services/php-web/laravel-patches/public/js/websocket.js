class IssWebSocket {
    constructor() {
        this.ws = null;
        this.reconnectInterval = 5000;
        this.maxReconnectAttempts = 10;
        this.reconnectAttempts = 0;
        this.callbacks = {
            onUpdate: [],
            onConnect: [],
            onDisconnect: []
        };
    }

    connect() {
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const host = window.location.hostname;
        const port = window.location.port ? `:${window.location.port}` : '';
        
        // Подключаемся к Rust WebSocket серверу
        const wsUrl = `${protocol}//${host}${port}/ws/iss`;
        
        this.ws = new WebSocket(wsUrl);
        
        this.ws.onopen = () => {
            console.log('WebSocket connected to ISS tracker');
            this.reconnectAttempts = 0;
            
            // Подписываемся на обновления
            this.ws.send(JSON.stringify({
                command: 'subscribe',
                timestamp: new Date().toISOString()
            }));
            
            this.callbacks.onConnect.forEach(callback => callback());
        };

        this.ws.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                
                switch (data.type) {
                    case 'init':
                        console.log('Initial ISS data received:', data.data);
                        this.callbacks.onUpdate.forEach(callback => callback(data.data));
                        break;
                        
                    case 'update':
                        console.log('ISS update received:', data.data);
                        this.callbacks.onUpdate.forEach(callback => callback(data.data));
                        break;
                        
                    case 'error':
                        console.error('WebSocket error:', data.message);
                        break;
                }
            } catch (error) {
                console.error('Error parsing WebSocket message:', error);
            }
        };

        this.ws.onclose = () => {
            console.log('WebSocket disconnected');
            this.callbacks.onDisconnect.forEach(callback => callback());
            
            // Попытка переподключения
            if (this.reconnectAttempts < this.maxReconnectAttempts) {
                setTimeout(() => {
                    this.reconnectAttempts++;
                    console.log(`Reconnecting... Attempt ${this.reconnectAttempts}`);
                    this.connect();
                }, this.reconnectInterval);
            }
        };

        this.ws.onerror = (error) => {
            console.error('WebSocket error:', error);
        };
    }

    disconnect() {
        if (this.ws) {
            this.ws.close();
        }
    }

    sendCommand(command, data = {}) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({
                command,
                ...data,
                timestamp: new Date().toISOString()
            }));
        }
    }

    // Регистрация колбэков
    onUpdate(callback) {
        this.callbacks.onUpdate.push(callback);
    }

    onConnect(callback) {
        this.callbacks.onConnect.push(callback);
    }

    onDisconnect(callback) {
        this.callbacks.onDisconnect.push(callback);
    }
}

// Глобальный экземпляр
window.issWebSocket = new IssWebSocket();