<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager</title>
    <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    @verbatim
    <div id="app" class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Task Manager</h1>
            <p class="text-gray-600">Manage your tasks with status progression</p>
        </div>

        <!-- Create Task Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Create New Task</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="text" v-model="newTask.title" placeholder="Task title"
                       class="border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="date" v-model="newTask.due_date"
                       class="border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <select v-model="newTask.priority"
                        class="border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="high">High Priority</option>
                    <option value="medium">Medium Priority</option>
                    <option value="low">Low Priority</option>
                </select>
                <button @click="createTask"
                        class="bg-blue-600 text-white rounded-lg px-4 py-2 hover:bg-blue-700 transition">
                    Add Task
                </button>
            </div>
            <div v-if="error" class="mt-4 p-3 bg-red-100 text-red-700 rounded-lg">{{ error }}</div>
            <div v-if="success" class="mt-4 p-3 bg-green-100 text-green-700 rounded-lg">{{ success }}</div>
        </div>

        <!-- Filter Tabs -->
        <div class="flex gap-2 mb-6">
            <button @click="filter = 'all'" :class="filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'" class="px-4 py-2 rounded-lg transition">All</button>
            <button @click="filter = 'pending'" :class="filter === 'pending' ? 'bg-yellow-600 text-white' : 'bg-gray-200 text-gray-700'" class="px-4 py-2 rounded-lg transition">Pending</button>
            <button @click="filter = 'in_progress'" :class="filter === 'in_progress' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'" class="px-4 py-2 rounded-lg transition">In Progress</button>
            <button @click="filter = 'done'" :class="filter === 'done' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700'" class="px-4 py-2 rounded-lg transition">Done</button>
        </div>

        <!-- Daily Report Section (BONUS) -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Daily Task Report</h2>
                <div class="flex gap-2">
                    <input type="date" v-model="reportDate" class="border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button @click="loadReport" class="bg-green-600 text-white rounded-lg px-4 py-2 hover:bg-green-700 transition">
                        Get Report
                    </button>
                </div>
            </div>

            <div v-if="reportLoading" class="text-center py-4 text-gray-500">Loading report...</div>

            <div v-else-if="reportData" class="overflow-x-auto">
                <div class="mb-4">
                    <p class="text-lg font-semibold">Date: <span class="text-blue-600">{{ reportData.date }}</span></p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                        <div class="bg-blue-50 rounded-lg p-3 text-center">
                            <p class="text-2xl font-bold text-blue-600">{{ reportData.statistics.total_tasks }}</p>
                            <p class="text-sm text-gray-600">Total Tasks</p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-3 text-center">
                            <p class="text-2xl font-bold text-green-600">{{ reportData.statistics.completed }}</p>
                            <p class="text-sm text-gray-600">Completed</p>
                        </div>
                        <div class="bg-yellow-50 rounded-lg p-3 text-center">
                            <p class="text-2xl font-bold text-yellow-600">{{ reportData.statistics.completion_rate }}</p>
                            <p class="text-sm text-gray-600">Completion Rate</p>
                        </div>
                    </div>
                </div>

                <table class="min-w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-2 text-left">Priority</th>
                            <th class="px-4 py-2 text-center">Pending</th>
                            <th class="px-4 py-2 text-center">In Progress</th>
                            <th class="px-4 py-2 text-center">Done</th>
                            <th class="px-4 py-2 text-center">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="priority in priorities" :key="priority" class="border-b">
                            <td class="px-4 py-2 font-semibold">
                                <span :class="priority === 'high' ? 'text-red-600' : (priority === 'medium' ? 'text-yellow-600' : 'text-green-600')">
                                    {{ getPriorityText(priority) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-center">{{ reportData.summary[priority].pending }}</td>
                            <td class="px-4 py-2 text-center">{{ reportData.summary[priority].in_progress }}</td>
                            <td class="px-4 py-2 text-center">{{ reportData.summary[priority].done }}</td>
                            <td class="px-4 py-2 text-center font-semibold">
                                {{ reportData.summary[priority].pending + reportData.summary[priority].in_progress + reportData.summary[priority].done }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else-if="reportError" class="text-center py-4 text-red-500">
                {{ reportError }}
            </div>

            <div v-else class="text-center py-4 text-gray-500">
                Select a date and click "Get Report" to view task summary
            </div>
        </div>

        <!-- Task List -->
        <div class="space-y-4">
            <div v-for="task in filteredTasks" :key="task.id" :class="getTaskCardClass(task.status)" class="rounded-lg shadow-md p-4 transition">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span :class="getPriorityBadgeClass(task.priority)" class="px-2 py-1 rounded-full text-xs font-semibold">
                                {{ getPriorityText(task.priority) }}
                            </span>
                            <span :class="getStatusBadgeClass(task.status)" class="px-2 py-1 rounded-full text-xs font-semibold">
                                {{ getStatusText(task.status) }}
                            </span>
                            <span class="text-sm text-gray-500">
                                {{ formatDate(task.due_date) }}
                            </span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800">{{ task.title }}</h3>
                    </div>
                    <div class="flex gap-2">
                        <select v-if="task.status !== 'done'"
                                @change="updateStatus(task.id, $event.target.value)"
                                :value="task.status"
                                class="border rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="pending" :disabled="task.status !== 'pending'">Pending</option>
                            <option value="in_progress" :disabled="task.status !== 'pending'">In Progress</option>
                            <option value="done" :disabled="task.status !== 'in_progress'">Done</option>
                        </select>
                        <button v-if="task.status === 'done'"
                                @click="deleteTask(task.id)"
                                class="bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 transition text-sm">
                            Delete
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="filteredTasks.length === 0" class="text-center py-8 text-gray-500">
                No tasks found. Create your first task!
            </div>
        </div>
    </div>
    @endverbatim

    <script>
        const api = axios.create({
            baseURL: '/api',
            headers: { 'Content-Type': 'application/json' }
        });

        new Vue({
            el: '#app',
            data: {
                tasks: [],
                newTask: {
                    title: '',
                    due_date: new Date().toISOString().split('T')[0],
                    priority: 'medium'
                },
                filter: 'all',
                error: '',
                success: '',
                reportDate: new Date().toISOString().split('T')[0],
                reportData: null,
                reportLoading: false,
                reportError: '',
                priorities: ['high', 'medium', 'low']
            },
            computed: {
                filteredTasks() {
                    if (this.filter === 'all') return this.tasks;
                    return this.tasks.filter(task => task.status === this.filter);
                }
            },
            mounted() {
                this.loadTasks();
            },
            methods: {
                async loadTasks() {
                    try {
                        const res = await api.get('/tasks');
                        this.tasks = res.data.data;
                    } catch(e) {
                        console.error('Failed to load tasks', e);
                    }
                },
                async createTask() {
                    this.error = '';
                    this.success = '';

                    if (!this.newTask.title) {
                        this.error = 'Please enter a task title';
                        return;
                    }

                    try {
                        const res = await api.post('/tasks', this.newTask);
                        this.success = res.data.message;
                        this.newTask.title = '';
                        this.loadTasks();
                        setTimeout(() => this.success = '', 3000);
                    } catch(e) {
                        if (e.response?.data?.errors) {
                            this.error = Object.values(e.response.data.errors).flat().join(', ');
                        } else {
                            this.error = e.response?.data?.message || 'Failed to create task';
                        }
                        setTimeout(() => this.error = '', 3000);
                    }
                },
                async updateStatus(id, status) {
                    try {
                        await api.patch(`/tasks/${id}/status`, { status });
                        this.loadTasks();
                    } catch(e) {
                        alert(e.response?.data?.message || 'Failed to update status');
                    }
                },
                async deleteTask(id) {
                    if (confirm('Are you sure you want to delete this task?')) {
                        try {
                            await api.delete(`/tasks/${id}`);
                            this.loadTasks();
                        } catch(e) {
                            alert(e.response?.data?.message || 'Failed to delete task');
                        }
                    }
                },
                async loadReport() {
                    this.reportLoading = true;
                    this.reportError = '';
                    this.reportData = null;

                    try {
                        const response = await api.get('/tasks/report', {
                            params: { date: this.reportDate }
                        });
                        this.reportData = response.data.data;
                    } catch (err) {
                        if (err.response?.status === 422) {
                            this.reportError = 'Invalid date format. Use YYYY-MM-DD';
                        } else if (err.response?.status === 400) {
                            this.reportError = 'Please enter a valid date';
                        } else {
                            this.reportError = err.response?.data?.message || 'Failed to load report';
                        }
                    } finally {
                        this.reportLoading = false;
                    }
                },
                formatDate(date) {
                    return new Date(date).toLocaleDateString();
                },
                getPriorityText(priority) {
                    return { high: 'High', medium: 'Medium', low: 'Low' }[priority];
                },
                getPriorityBadgeClass(priority) {
                    return {
                        high: 'bg-red-100 text-red-800',
                        medium: 'bg-yellow-100 text-yellow-800',
                        low: 'bg-green-100 text-green-800'
                    }[priority];
                },
                getStatusText(status) {
                    return { pending: 'Pending', in_progress: 'In Progress', done: 'Done' }[status];
                },
                getStatusBadgeClass(status) {
                    return {
                        pending: 'bg-yellow-100 text-yellow-800',
                        in_progress: 'bg-blue-100 text-blue-800',
                        done: 'bg-green-100 text-green-800'
                    }[status];
                },
                getTaskCardClass(status) {
                    return {
                        pending: 'border-l-4 border-yellow-500',
                        in_progress: 'border-l-4 border-blue-500',
                        done: 'border-l-4 border-green-500 bg-gray-50'
                    }[status];
                }
            }
        });
    </script>
</body>
</html>
