<template>
    <div>
        <div class="container">
            <div class="row" v-if="countriesDoneMapping">
                <table style="width:100%" class="table table-striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" @change="selectAll" value="selectAll"></th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Telephone</th>
                            <th>Sale Status</th>
                            <th>Campaign ID</th>
                            <th>Country</th>
                        </tr>
                    </thead>
                    <tbody>
                         <tr v-for="customer in customers.data">
                            <th><input type="checkbox" class="saved-checkbox" name="saved-selection" :value="customer.id" v-model="selectedCustomers"></th>
                            <td>{{ customer.FirstName }}</td>
                            <td>{{ customer.LastName }}</td>
                            <td>{{ customer.Phone }}</td>
                            <td>{{ customer.saleStatus }}</td>
                            <td>{{ customer.campaignId }}</td>
                            <td>{{ getCountryName(customer.countryId) }}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="pull-right" v-if="selectedCustomers.length">
                    <button class="btn btn-success" @click="sendCustomers" v-text="isloading ? 'Loading...' : 'SEND'" :disabled="isloading"></button>
                </div>
                <div class="text-center" v-if="loggerText">
                    <p class="well logger" v-html="loggerText"></p>
                </div>
            </div>
        </div>
    </div>
</template>
<style>

</style>
<script type="text/babel">

    export default{
        data(){
            return{
                selectedCustomers: [],
                loggerText: "",
                isloading: false,
                mappedCountries: []
            }
        },

        created(){
            _.map(this.countries, country => {
                this.mappedCountries[country.id] = country.name;
            });
        },

        props:{
            customers:{
                required: false
            },
            countries:{
                required: false,
                type: Array
            }
        },

        computed: {
            countriesDoneMapping(){
                return !! this.mappedCountries.length;
            }
        },

        methods:{
            selectAll(e){
                this.selectedCustomers = [];

                if(e.target.checked){
                    _.map(this.customers.data, customer => {
                        this. selectedCustomers.push(customer.id);
                    });
                }

            },
            getCountryName(countryId){
                if(!this.mappedCountries.length)
                    return countryId;

                return this.mappedCountries[countryId];
            },
            sendCustomers(){
                this.isloading = true;
                this.loggerText = "";

                axios.post("/send-customers", { customersIds: this.selectedCustomers })
                    .then(response => {
                        this.loggerText = response.data.message;
                        this.isloading = false;
                    })
                    .catch(error => {
                        this.isloading = false;
                    });
            }
        }
    }
</script>

<style>
    .logger{
        margin-top: 100px;
        text-align: left;
        color: #313131;
    }
    .entry-error{
        color: red;
    }
    .entry-ok{
        color: green;
    }
</style>
