    </div>
    
    <script>
        const API_BASE = '/api';
        
        async function apiCall(endpoint, method = 'GET', data = null) {
            const token = localStorage.getItem('auth_token');
            
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                }
            };
            
            if (token) {
                options.headers['Authorization'] = `Bearer ${token}`;
            }
            
            if (data && method !== 'GET') {
                options.body = JSON.stringify(data);
            }
            
            try {
                const response = await fetch(API_BASE + endpoint, options);
                const result = await response.json();
                
                if (!response.ok) {
                    throw new Error(result.error || 'API error');
                }
                
                return result;
            } catch (error) {
                console.error('API call failed:', error);
                throw error;
            }
        }
        
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 
                'bg-blue-500'
            } text-white z-50`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
        
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
        
        function getPriorityIcon(priority) {
            const icons = {
                'urgent': '<i class="fas fa-exclamation-circle text-red-500"></i>',
                'high': '<i class="fas fa-arrow-up text-orange-500"></i>',
                'medium': '<i class="fas fa-minus text-yellow-500"></i>',
                'low': '<i class="fas fa-arrow-down text-green-500"></i>'
            };
            return icons[priority] || '';
        }
        
        function getStatusBadge(status) {
            const badges = {
                'pending': '<span class="badge badge-pending">Ожидает</span>',
                'in_progress': '<span class="badge badge-in-progress">В работе</span>',
                'completed': '<span class="badge badge-completed">Выполнено</span>',
                'cancelled': '<span class="badge badge-cancelled">Отменено</span>'
            };
            return badges[status] || status;
        }
        
        function confirmAction(message) {
            return confirm(message);
        }
    </script>
</body>
</html>
