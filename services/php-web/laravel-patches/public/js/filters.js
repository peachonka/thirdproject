class DataFilters {
    constructor() {
        this.filters = {
            iss: {},
            osdr: {},
            neo: {},
            global: {}
        };
    }

    // Применить фильтры к таблице
    static filterTable(tableId, filters) {
        const table = document.getElementById(tableId);
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');
        let visibleCount = 0;

        rows.forEach(row => {
            let show = true;

            // Проверка каждого фильтра
            for (const [column, value] of Object.entries(filters)) {
                if (!value) continue;

                const cell = row.querySelector(`td[data-column="${column}"]`) || 
                            row.cells[this.getColumnIndex(table, column)];
                
                if (cell) {
                    const cellValue = cell.textContent.toLowerCase();
                    const filterValue = value.toLowerCase();

                    if (!cellValue.includes(filterValue)) {
                        show = false;
                        break;
                    }
                }
            }

            // Показать/скрыть строку
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        // Обновить счетчик
        const counter = document.getElementById(`${tableId}-count`);
        if (counter) {
            counter.textContent = `Показано: ${visibleCount}`;
        }

        return visibleCount;
    }

    // Получить индекс колонки по названию
    static getColumnIndex(table, columnName) {
        const headers = table.querySelectorAll('thead th');
        for (let i = 0; i < headers.length; i++) {
            if (headers[i].textContent.toLowerCase().includes(columnName.toLowerCase())) {
                return i;
            }
        }
        return -1;
    }

    // Сортировка таблицы
    static sortTable(tableId, columnIndex, direction = 'asc') {
        const table = document.getElementById(tableId);
        if (!table) return;

        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        rows.sort((a, b) => {
            const aVal = a.cells[columnIndex]?.textContent || '';
            const bVal = b.cells[columnIndex]?.textContent || '';

            // Попробуем преобразовать в числа
            const aNum = parseFloat(aVal.replace(/[^\d.-]/g, ''));
            const bNum = parseFloat(bVal.replace(/[^\d.-]/g, ''));

            if (!isNaN(aNum) && !isNaN(bNum)) {
                return direction === 'asc' ? aNum - bNum : bNum - aNum;
            }

            // Строковое сравнение
            return direction === 'asc' 
                ? aVal.localeCompare(bVal)
                : bVal.localeCompare(aVal);
        });

        // Перестроить таблицу
        rows.forEach(row => tbody.appendChild(row));

        // Обновить стрелки сортировки
        this.updateSortIndicators(tableId, columnIndex, direction);
    }

    // Обновить индикаторы сортировки
    static updateSortIndicators(tableId, columnIndex, direction) {
        const table = document.getElementById(tableId);
        const headers = table.querySelectorAll('thead th');

        headers.forEach((header, index) => {
            header.classList.remove('sort-asc', 'sort-desc');
            const icon = header.querySelector('.sort-icon');
            if (icon) icon.remove();

            if (index === columnIndex) {
                header.classList.add(`sort-${direction}`);
                const icon = document.createElement('i');
                icon.className = `fas fa-sort-${direction === 'asc' ? 'up' : 'down'} ms-2 sort-icon`;
                header.appendChild(icon);
            }
        });
    }

    // Фильтрация с сервера (AJAX)
    static async filterWithAjax(endpoint, filters) {
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify(filters)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Filter error:', error);
            throw error;
        }
    }

    // Обновить UI фильтров
    static updateFilterUI(containerId, filters) {
        const container = document.getElementById(containerId);
        if (!container) return;

        // Очистить старые фильтры
        const oldFilters = container.querySelectorAll('.active-filter');
        oldFilters.forEach(filter => filter.remove());

        // Добавить новые активные фильтры
        for (const [key, value] of Object.entries(filters)) {
            if (value) {
                const badge = document.createElement('span');
                badge.className = 'badge bg-primary me-2 mb-2 active-filter';
                badge.innerHTML = `
                    ${key}: ${value}
                    <button type="button" class="btn-close btn-close-white ms-1" 
                            onclick="DataFilters.removeFilter('${key}')"></button>
                `;
                container.appendChild(badge);
            }
        }
    }

    // Удалить фильтр
    static removeFilter(filterKey) {
        // Реализация зависит от контекста
        console.log(`Remove filter: ${filterKey}`);
        // Здесь нужно обновить состояние фильтров и перефильтровать данные
    }
}

// Инициализация фильтров при загрузке
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация сортировки таблиц
    document.querySelectorAll('th[data-sortable]').forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const tableId = this.closest('table').id;
            const columnIndex = Array.from(this.parentNode.children).indexOf(this);
            const currentDirection = this.classList.contains('sort-asc') ? 'desc' : 'asc';
            
            DataFilters.sortTable(tableId, columnIndex, currentDirection);
        });
    });

    // Инициализация полей фильтрации
    document.querySelectorAll('[data-filter]').forEach(input => {
        input.addEventListener('input', function() {
            const tableId = this.dataset.filter;
            const column = this.dataset.column || this.name;
            const value = this.value;

            const filters = { [column]: value };
            DataFilters.filterTable(tableId, filters);
            DataFilters.updateFilterUI(`${tableId}-filters`, filters);
        });
    });

    // Кнопки сброса фильтров
    document.querySelectorAll('.reset-filters').forEach(button => {
        button.addEventListener('click', function() {
            const tableId = this.dataset.table;
            const inputs = document.querySelectorAll(`[data-filter="${tableId}"]`);
            
            inputs.forEach(input => {
                input.value = '';
            });
            
            DataFilters.filterTable(tableId, {});
            DataFilters.updateFilterUI(`${tableId}-filters`, {});
        });
    });
});