// Endereço da API (o servidor PHP)
const API_URL = 'http://localhost:8000';


const { createApp } = Vue;

createApp({
    // O estado da tela: os dados que o Vue observa
    data() {
        return {
            users: [],       // a lista de usuários vinda da API
            loading: false,  // true enquanto espera a resposta
            error: null,     // a mensagem de erro, se houver
        };
    },

    // Roda automaticamente quando a página termina de montar
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
    },
}).mount('#app');