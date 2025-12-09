const vue_app = Vue.createApp({
    data() {
        return {
            // ⭐ 設置 API 的基礎路徑 ⭐
            base_dir: '/todo-list-php-vue/app/crud/',

            vtxt: {
                title: "待辨事項清單",
                etitle: "ToDoList",
            },
            vclass: { 
                title: "display-6 gf gfw900 text-center text-black text-shine-m",
                card:"card",
                cardhd:"card-header fs-5 gf gfw500",
                cardbd:"card-body",
                cardtit:"card-title d-grid col-10 mx-auto mb-2 fs-5 gf gfw500",
                cardtxt:"card-text text-start fs-6",
                cardft: "card-footer gf gfw500 mx-3",
                navtabs:"nav nav-tabs card-header-tabs",
                navitem:"nav-item",
                navlink_act:"nav-link active",
                navlink:"nav-link",
            },
            vdata: {
                today_date: "",
                todo: "",
                sub_todo:"",
                todos: [],
                
                completed: [],
                inprogress: [],
                visible: 'all',

                edit_id: '',
                edit_title:'',
                edit_todo:[],
                
                sedit_id: '',
                sedit_title:'',
                sedit_todo: [],
                addsub_todoid: '',

                sub_collapse:true,
            },
            user_id: 1,      // 欲抓取的用戶 ID
            isLoading: true, // 載入狀態
            error: null,     // 錯誤訊息
        }
    },
    methods: {
        // 定義 fetchData 函式來執行 API 呼叫
        async fetchTodos() {
            this.isLoading = true;
            this.error = null;
            // ⭐ 組合 API URL: base_dir + 檔案名稱 + 參數 ⭐
            const API_ENDPOINT = 'read_todos.php';
            // 設定您的 API 路徑，帶上 user_id 參數
            const API_URL = `${this.base_dir}${API_ENDPOINT}?user_id=${this.user_id}`;  
            try {
                // 步驟 1: 發送請求
                const response = await fetch(API_URL);
                // 檢查 HTTP 狀態碼是否成功 (200-299)
                if (!response.ok) {
                    this.error = `API 請求失敗，狀態碼: ${response.status}`;
                    throw new Error(this.error);
                }
                // 步驟 2: 解析 JSON 資料
                const data = await response.json();
                // 步驟 3: 將資料賦值給 Vue data 屬性 (vdata.todos)
                if (data.error) {
                    // 處理 PHP 返回的錯誤 (例如 user_id 無效)
                    this.error = data.error;
                    this.vdata.todos = [];
                } else {
                    this.vdata.todos = data; 
                }
                
            } catch (error) {
                // 處理網路錯誤或上方拋出的錯誤
                if (!this.error) {
                    this.error = '網路連線或資料處理錯誤。';
                }
                this.vdata.todos = [];
            } finally {
                this.isLoading = false;
            }
        },
        /**
         * 獲取並格式化今天的日期為 YYYY-MM-DD 格式
         */
        getTodayDate() {
            var vm = this;
            const now = new Date();
            
            // 獲取年、月、日
            const year = now.getFullYear();
            // 月份是從 0 開始的，所以需要 +1
            const month = String(now.getMonth() + 1).padStart(2, '0'); 
            const day = String(now.getDate()).padStart(2, '0');
            
            // 組合成 YYYY-MM-DD 格式
            const formatted_date = `${year}-${month}-${day}`;
            
            // 將結果存入 Vue data
            vm.vdata.today_date = formatted_date;
        },
        Ctodo(){
            let my = this;
            let todo_add = (my.vdata.todo).trim();
            if ( !todo_add ) { return };       //基本檢查：如果輸入是空的，則不執行任何操作
            //準備待新增的資料物件
            // 暫時的 ID，用於前端追蹤和後續替換 (例如：'temp_1765106828000')
            const tempId = 'temp_' + Date.now().toString();
            const newTodoItem = {
                id: tempId, // 暫時的, 等會由後端資料庫自動生成ID
                title: todo_add,
                deadline: "9999-12-31",             //預設空字串
                sub_todos: []             //先建立好空的陣列, 將來push才不會出錯  
            };
            my.vdata.todos.push(newTodoItem);
            my.vdata.todo = "";                //新增之後清空

            // 【⭐ AJAX 串接後端：只傳送業務資料，不傳送 id (或傳 null) ⭐】
            // 建立一個用於傳送到後端的物件，並移除前端的臨時 ID
            // 這裡我們只傳送必要的欄位給後端;; 不使用delete newTodoItem.id 是因為我們不想改變原本的物件, 以免影響前端顯示
            // 如果後端需要其他欄位，請根據後端需求進行調整;; 這裡假設後端會自動生成 ID
            const payload = {
                title: newTodoItem.title,
                deadline: newTodoItem.deadline,
                user_id: my.user_id 
            };
            // 新增時也需要傳入用戶 ID
            // 依照後端設計，如果 sub_todos 是子表，這裡也不需要傳送
            // sub_todos: newTodoItem.sub_todos

            // ⭐ 組合 API URL: base_dir + 檔案名稱 ⭐
            const API_ENDPOINT = 'create_todos.php';
            const API_URL = `${this.base_dir}${API_ENDPOINT}`;
            // 發送 AJAX 請求 (這裡使用 jQuery $.ajax 示範)
            $.ajax({
                url: API_URL, 
                type: 'POST', 
                data: JSON.stringify(payload),
                contentType: 'application/json; charset=utf-8', 
                dataType: 'json', 
                
                success: function(response) {
                    if (response.status === 'success' && response.new_db_id) {
                        const index = my.vdata.todos.findIndex(item => item.id === tempId);
                        if (index !== -1) {
                            my.vdata.todos[index].id = response.new_db_id;
                        }
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    // 失敗時移除前端項目
                    const index = my.vdata.todos.findIndex(item => item.id === tempId);
                    if (index !== -1) {
                        my.vdata.todos.splice(index, 1);
                    }
                }
            });
        },
        Utodo(obj) { 
            var vm = this;
            vm.vdata.edit_id = obj.item_id;
            vm.vdata.edit_title = obj.title;
            vm.vdata.edit_todo = obj;
        },
        toggleComplete(obj) {
            let vm = this;
            let new_completed_date;
            if (obj.completed) {
                new_completed_date = vm.vdata.today_date;
            } else {
                new_completed_date = null;
            };

            // 2. 準備 API 請求
            const API_ENDPOINT = 'check_todos.php';
            const API_URL = `${vm.base_dir}${API_ENDPOINT}`;
            const payload = {
                item_id: obj.item_id,
                user_id: vm.user_id || 1, 
                completed_date: new_completed_date // 將日期/標記傳輸給後端
            };
            // 發送 AJAX 請求
            $.ajax({
                url: API_URL, 
                type: 'POST', 
                data: JSON.stringify(payload),
                contentType: 'application/json; charset=utf-8', 
                dataType: 'json', 
                success: function(response) {
                    if (response.status !== 'success') {
                        // 如果後端更新失敗，則將前端狀態改回
                        obj.completed = !new_completed_status; 
                        console.error('後端更新完成狀態失敗:', response.message);
                        Swal.fire('錯誤', '完成狀態更新失敗，請稍後再試。', 'error');
                    }
                    // 成功時，Vue 的 computed properties 會自動重新計算
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    // 網路錯誤，將前端狀態改回
                    obj.completed = !new_completed_status;
                    console.error('更新完成狀態失敗:', errorThrown);
                    Swal.fire('網路錯誤', '無法連線到伺服器。', 'error');
                }
            });
        },
        Dtodo(obj) {
            Swal.fire({
                title: "確定刪除'待辦事項'嗎?",
                text: "刪除後您將無法回復此資料!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "刪除",
                cancelButtonText: "取消",
            }).then( (result) => {
                if (result.isConfirmed) {
                    let vm = this;
                    let obj_idx = vm.vdata.todos.indexOf(obj);
                    vm.vdata.todos.splice(obj_idx, 1);
                    // ⭐️ 準備要發送的資料： item_id 和 user_id ⭐️
                    const payload = {
                        item_id: obj.item_id,
                        // 假設您在 Vue 實例中定義了 user_id 變數
                        user_id: vm.user_id || 1 // 使用實際的 user_id 或預設值
                    };

                    const API_ENDPOINT = 'dele_todos.php';
                    const API_URL = `${vm.base_dir}${API_ENDPOINT}`;

                    // 發送 AJAX 請求到後端
                    $.ajax({
                        url: API_URL, 
                        type: 'POST', // 雖然是刪除，但為兼容性通常使用 POST
                        data: JSON.stringify(payload),
                        contentType: 'application/json; charset=utf-8', 
                        dataType: 'json', 
                        success: function(response) {
                            if (response.status === 'success') {
                                // 後端成功刪除後，才從前端陣列中移除
                                let obj_idx = vm.vdata.todos.findIndex(item => item.item_id === obj.item_id);
                                if (obj_idx !== -1) {
                                    vm.vdata.todos.splice(obj_idx, 1);
                                    // 重新計算分類 (由於 todos 是 computed property 的依賴，這裡不需要手動更新 completed/inprogress)
                                    
                                    Swal.fire('已刪除!', '待辦事項已成功清除', 'success');
                                }
                            } else {
                                Swal.fire('刪除失敗', response.message, 'error');
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            Swal.fire('網路錯誤', '無法連線到伺服器進行刪除。<br> 按F5請重新整理', 'error');
                        }
                    });
                }
            });
        },
        clear_todos() {
            swalWithBootstrapButtons.fire({
                title: '確定全部刪除嗎?',
                text: "---所有資料連明細都將清空,而且無法復原---",
                // width: 500,
                showCancelButton: true,
                confirmButtonText: '確定',
                cancelButtonText: '取消',
            }).then((result) => {
                if (result.isConfirmed) {
                    var vm = this;
                    vm.vdata.todos = [];
                    
                    swalWithBootstrapButtons.fire(
                        '已刪除!',
                        '您的所有資料,已完全清除',
                        '成功'
                    );
                };
            })
            Swal.fire({
                title: '確定全部刪除嗎?',
                text: "---所有資料連明細都將清空,而且無法復原---",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "確定, 全部刪除",
                cancelButtonText: "取消",
            }).then((result) => {
                if (result.isConfirmed) {
                    var vm = this;
                    vm.vdata.todos = [];
                    
                    swalWithBootstrapButtons.fire(
                        '已刪除!',
                        '您的所有資料,已完全清除',
                        '成功'
                    );
                }
            });
        },
        edit_done(obj) {
            var vm = this;
            obj.title = vm.vdata.edit_title;    //  於此同時, todos也修改了( todos.title=item.title=obj.title)
            vm.vdata.edit_id = '';
            vm.vdata.edit_title = '';
            vm.vdata.edit_todo='';
            //  準備 API 請求
            const API_ENDPOINT = 'update_todos.php';
            const API_URL = `${vm.base_dir}${API_ENDPOINT}`;
            const payload = {
                item_id: obj.item_id,
                user_id: vm.user_id || 1, 
                title: obj.title,
            };
            $.ajax({
                url: API_URL, 
                type: 'POST', 
                data: JSON.stringify(payload),
                contentType: 'application/json; charset=utf-8', 
                dataType: 'json', 
                success: function(response) {
                    if (response.status !== 'success') {
                        // 如果後端更新失敗，則將前端狀態改回
                        console.error('後端更新完成狀態失敗:', response.message);
                        Swal.fire('錯誤', '完成狀態更新失敗，請稍後再試。', 'error');
                    }
                    // 成功時，Vue 的 computed properties 會自動重新計算
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    // 網路錯誤，將前端狀態改回
                    console.error('更新完成狀態失敗:', errorThrown);
                    Swal.fire('網路錯誤', '無法連線到伺服器。', 'error');
                }
            });
        },
        edit_cancel(obj) {
            var vm = this;
            vm.vdata.edit_id = '';
            vm.vdata.edit_title = '';
            vm.vdata.edit_todo='';
        },
        addsub_todo(todo_id) {
            var vm = this;
            vm.vdata.sub_collapse = false;     //新增明細時, 將收合『取消』-->就是打開
            vm.vdata.addsub_todoid = todo_id;
        },
        sub_todoC_done(obj) {
            var vm = this;
            // 關閉前,先新增------------------begin
            var sub_todo_add = (vm.vdata.sub_todo).trim();
            if (!sub_todo_add) {
                vm.vdata.addsub_todoid = ''; 
                return;
            };
            var obj_idx = vm.vdata.todos.indexOf(obj);        // 1-先找到整個物件的索引位置
            var todo = vm.vdata.todos[obj_idx];               // 2-取出該物件    
            todo.sub_todos.push
            ({
                id: Date.now().toString(),
                title: sub_todo_add,
                completed: false,
            });
            // 關閉前,先新增------------------ending
            vm.vdata.addsub_todoid = '';                        // v-if="vdata.addsub_todoid==item.id"<--不成立, 關閉該<input>
        },
        stodoD(obj,sub_obj) {
            Swal.fire({
            title: "確定刪除明細嗎?",
            text: "刪除後您將無法回復此資料!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "刪除",
            cancelButtonText: "取消",
            }).then((result) => {
                if (result.isConfirmed) {
                    var vm = this;
                    var obj_idx = vm.vdata.todos.indexOf(obj);               // 1-先找到整個物件的索引位置
                    var todo = vm.vdata.todos[obj_idx];                      // 2-取出該物件    
                    var sub_obj_idx = todo.sub_todos.indexOf(sub_obj);      // 3-在該物件的sub_todos找到該物件的索引
                    vm.vdata.todos[obj_idx].sub_todos.splice(sub_obj_idx,1); // 4-移除該sub_todos
                }
            });
        },
        stodoU(obj) { 
            var vm = this;
            vm.vdata.sedit_id = obj.item_id;
            vm.vdata.sedit_todo = obj;
            vm.vdata.sedit_title = obj.title;
        },
        sedit_done(obj) {
            var vm = this;
            obj.title = vm.vdata.sedit_title;    //  於此同時, todos也修改了( todos.title=item.title=obj.title)
            vm.vdata.sedit_id = '';
            vm.vdata.sedit_title = '';
            vm.vdata.sedit_todo='';
        },
        sedit_cancel(obj) {
            var vm = this;
            vm.vdata.sedit_id = '';
            vm.vdata.sedit_title = '';
            vm.vdata.sedit_todo='';
        },

    },
    computed: {
        filter_todo() {
            var vm = this;
            var rtn;
            vm.vdata.completed = [];
            vm.vdata.inprogress = [];
            vm.vdata.todos.forEach(function(item,index) {
                if (item.completed){
                    vm.vdata.completed.push(item);
                } else {
                    vm.vdata.inprogress.push(item);
                }
            });
            switch (vm.vdata.visible) {
                case 'all':
                    return vm.vdata.todos;
                case 'inprogress':
                    return vm.vdata.inprogress;
                case 'completed':
                    return vm.vdata.completed;
            }
        }
    },
    // 在 Vue 實例掛載到 DOM 後執行
    mounted() {
        this.fetchTodos(); // 啟動時自動呼叫 API 抓取資料
        this.getTodayDate(); // 啟動時取得今天日期
    }
});
vue_app.mount('#vue_show');  /*掛載在 index.html : id=vue_show ----*/