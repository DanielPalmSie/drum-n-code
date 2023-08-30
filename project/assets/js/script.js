const token = localStorage.getItem('JWT_token');


const filterAllBtn = document.getElementById('filter-all');
const filterTodoBtn = document.getElementById('filter-todo');
const filterDoneBtn = document.getElementById('filter-done');
const filterPriorityBtn = document.getElementById('filter-priority');

filterAllBtn.addEventListener('click', () => fetchTasks('all'));
filterTodoBtn.addEventListener('click', () => fetchTasks('todo'));
filterDoneBtn.addEventListener('click', () => fetchTasks('done'));
filterPriorityBtn.addEventListener('click', applyPriorityFilter);

function fetchTasks(status) {
    const taskList = document.getElementById('task-list');
    taskList.innerHTML = '';

    const url = `/api/task-list?status=${status}&sortOrder=${sortOrder}`;
    fetch(url, {
        method: 'GET',
        headers: {
            Authorization: `Bearer ${token}`,
        },
    })
        .then(response => response.json())
        .then(data => {
            data.forEach(task => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                        <td>${task.id}</td>
                        <td>${task.title}</td>
                        <td>${task.status}</td>
                        <td>${task.priority}</td>
                        <td>${task.created}</td>
                        <td>${task.completed}</td>
                        <td>
            <a href="javascript:void(0);" onclick="editTask(${task.id})"><i class="fas fa-edit"></i></a>
            <a href="javascript:void(0);" onclick="deleteTask(${task.id})"><i class="fas fa-trash-alt"></i></a>
            <td><input type="checkbox" class="task-checkbox" data-task-id="${task.id}" onchange="markTaskCompleted(this)" title="Mark as complete"></td>
        </td>
                    `;
                taskList.appendChild(tr);
            });
        })
        .catch(error => console.error('Error fetching task list:', error));
}

function applyPriorityFilter() {
    const minPriority = document.getElementById('min-priority').value;
    const maxPriority = document.getElementById('max-priority').value;
    const priorityRange = `${minPriority}-${maxPriority}`;
    fetchTasksWithPriority(priorityRange);
}

function fetchTasksWithPriority(priorityRange) {
    const taskList = document.getElementById('task-list');
    taskList.innerHTML = '';

    const url = `/api/task-list?priority=${priorityRange}&sortOrder=${sortOrder}`;
    fetch(url, {
        method: 'GET',
        headers: {
            Authorization: `Bearer ${token}`,
        },
    })
        .then(response => response.json())
        .then(data => {
            data.forEach(task => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                        <td>${task.id}</td>
                        <td>${task.title}</td>
                        <td>${task.status}</td>
                        <td>${task.priority}</td>
                        <td>${task.created}</td>
                        <td>${task.completed}</td>
                        <td>
            <a href="javascript:void(0);" onclick="editTask(${task.id})"><i class="fas fa-edit"></i></a>
            <a href="javascript:void(0);" onclick="deleteTask(${task.id})"><i class="fas fa-trash-alt"></i></a>
        </td>
        <td><input type="checkbox" class="task-checkbox" data-task-id="${task.id}" onchange="markTaskCompleted(this)" title="Mark as complete"></td>
                    `;
                taskList.appendChild(tr);
            });
        })
        .catch(error => console.error('Error fetching task list:', error));
}

function fetchTasksWithSorting(sortField) {
    const taskList = document.getElementById('task-list');
    taskList.innerHTML = '';

    const url = `/api/task-list?sortBy=${sortField}&sortOrder=${sortOrder}`;
    fetch(url, {
        method: 'GET',
        headers: {
            Authorization: `Bearer ${token}`,
        },
    })
        .then(response => response.json())
        .then(data => {
            data.forEach(task => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                        <td>${task.id}</td>
                        <td>${task.title}</td>
                        <td>${task.status}</td>
                        <td>${task.priority}</td>
                        <td>${task.created}</td>
                        <td>${task.completed}</td>
                        <td>
            <a href="javascript:void(0);" onclick="editTask(${task.id})"><i class="fas fa-edit"></i></a>
            <a href="javascript:void(0);" onclick="deleteTask(${task.id})"><i class="fas fa-trash-alt"></i></a>
        </td>
        <td><input type="checkbox" class="task-checkbox" data-task-id="${task.id}" onchange="markTaskCompleted(this)" title="Mark as complete"></td>
                    `;
                taskList.appendChild(tr);
            });
        })
        .catch(error => console.error('Error fetching task list:', error));
}