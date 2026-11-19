// Endereço da API (o servidor PHP)
const API_URL = 'http://localhost:8000';

function emptyForm() {
    return { name: '', email: '', login: '', password: '', active: true };
}
const { createApp } = Vue;

createApp({
    // O estado da tela: os dados que o Vue observa
    data() {
        return {
            users: [],
            loading: false,
            error: null,

            form: emptyForm(),// campos do formulário
            editingId: null,// null = cadastro . id = edição
            saving: false,
            formError: null,
            successMessage: null,
            actionError: null
        };
    },

    mounted() {
        this.loadUsers();
    },

    methods: {
        async loadUsers() {
            this.loading = true;
            this.error = null;

            try {
                const response = await fetch(`${API_URL}/users`);

                if (!response.ok) {
                    throw new Error('A API respondeu com erro');
                }

                this.users = await response.json();
            } catch (e) {
                this.error = 'Não foi possível carregar os usuários. Verifique se a API está rodando.';
            } finally {
                this.loading = false;
            }
        },

        // Converte a data UTC da API para o horário local
        formatDate(isoDate) {
            return new Date(isoDate).toLocaleString('pt-BR');
        },

        // preenche o formulário com os dados do usuário
        editUser(user) {
            this.editingId = user.id;
            this.form = {
                name: user.name,
                email: user.email,
                login: user.login,
                password: '',
                active: user.active,
            };
            this.formError = null;
        },

        // Volta o formulário ao modo de cadastro (vazio)
        resetForm() {
            this.editingId = null;
            this.form = emptyForm();
            this.formError = null;
        },

        // Botão "Salvar": cadastra (POST) ou edita (PUT)
        async saveUser() {
            this.saving = true;
            this.formError = null;

            const isEditing = this.editingId !== null;
            const url = isEditing ? `${API_URL}/users/${this.editingId}` : `${API_URL}/users`;
            const method = isEditing ? 'PUT' : 'POST';

            const body = {
                name: this.form.name,
                email: this.form.email,
                login: this.form.login,
                active: this.form.active,
            };

            //  envia a senha apenas se foi preenchida (na edição, vazia = manter a atual)
            if (this.form.password !== '') {
                body.password = this.form.password;
            }

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body),
                });

                const data = await response.json();

                if (!response.ok) {
                    // mostra a mensagem de erro que a API devolveu (ou um erro genérico se não houver um retorno mapeado)
                    this.formError = data.error ?? 'Não foi possível salvar o usuário.';
                    return;
                }

                this.showSuccess(isEditing ? 'Usuário atualizado com sucesso!' : 'Usuário cadastrado com sucesso!');
                this.resetForm();
                await this.loadUsers();
            } catch (e) {
                this.formError = 'Não foi possível conectar à API. Tente novamente.';
            } finally {
                this.saving = false;
            }
        },

        // mostra o aviso de sucesso por 3 segundos
        showSuccess(message) {
            this.successMessage = message;
            setTimeout(() => {
                this.successMessage = null;
            }, 3000);
        },

        async deleteUser(user) {
            if (!confirm(`Deseja realmente excluir o usuário "${user.name}"?`)) {
                return;
            }

            this.actionError = null;
            this.loading = true;

            //exclui e mostra a mensagem de sucesso ou de erro
            try {
                const response = await fetch(`${API_URL}/users/${user.id}`, { method: 'DELETE' });

                if (response.ok) {
                    this.showSuccess('Usuário excluído com sucesso!');
                } else {
                    this.actionError = 'Não foi possível excluir o usuário.';
                }
            } catch (e) {
                this.actionError = 'Não foi possível conectar à API.';
            }

            //recarrega a listagem sempre, independente de sucesso ou erro.
            await this.loadUsers();
        },
    },
}).mount('#app');