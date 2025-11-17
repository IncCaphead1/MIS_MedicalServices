// Сортировка таблиц
document.addEventListener('DOMContentLoaded', function() {
    const tables = document.querySelectorAll('table[id]');
    
    tables.forEach(table => {
        const headers = table.querySelectorAll('th[data-sort]');
        
        headers.forEach(header => {
            header.addEventListener('click', function() {
                const column = this.getAttribute('data-sort');
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                
                // Определяем направление сортировки
                const isAscending = !this.classList.contains('asc');
                this.classList.toggle('asc', isAscending);
                this.classList.toggle('desc', !isAscending);
                
                // Сбрасываем сортировку у других заголовков
                headers.forEach(h => {
                    if (h !== header) {
                        h.classList.remove('asc', 'desc');
                    }
                });
                
                // Сортируем строки
                rows.sort((a, b) => {
                    const aValue = a.querySelector(`td:nth-child(${Array.from(headers).indexOf(header) + 1})`).textContent;
                    const bValue = b.querySelector(`td:nth-child(${Array.from(headers).indexOf(header) + 1})`).textContent;
                    
                    // Пытаемся преобразовать в числа для числовой сортировки
                    const aNum = parseFloat(aValue.replace(/\s/g, '').replace(',', '.'));
                    const bNum = parseFloat(bValue.replace(/\s/g, '').replace(',', '.'));
                    
                    if (!isNaN(aNum) && !isNaN(bNum)) {
                        return isAscending ? aNum - bNum : bNum - aNum;
                    } else {
                        return isAscending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
                    }
                });
                
                // Очищаем и перезаполняем tbody
                tbody.innerHTML = '';
                rows.forEach(row => tbody.appendChild(row));
            });
        });
    });
});

// Подтверждение удаления
function confirmDelete(message = 'Вы уверены, что хотите удалить эту запись?') {
    return confirm(message);
}

// Валидация форм
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#dc3545';
            isValid = false;
        } else {
            field.style.borderColor = '';
        }
    });
    
    return isValid;
}

// Поиск в таблицах
function setupTableSearch(tableId, searchInputId) {
    const searchInput = document.getElementById(searchInputId);
    const table = document.getElementById(tableId);
    
    if (searchInput && table) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
}

// Автозаполнение дат
document.addEventListener('DOMContentLoaded', function() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value && input.name.includes('date')) {
            input.value = new Date().toISOString().split('T')[0];
        }
    });
});

// Уведомления
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.textContent = message;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '1000';
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}