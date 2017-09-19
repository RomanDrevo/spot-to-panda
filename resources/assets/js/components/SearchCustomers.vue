<template>
    <div>

        <div class="container">
            <div class="row">
                <div class="col-md-8 col-md-offset-2">
                    <div class="panel panel-default">
                        <div class="panel-heading">Transfer customers from 72Option to Roiteks</div>

                        <div class="panel-body">

                            <form method="post" action="">
                                <p class="errors-wrapper well" v-html="errorText" v-if="errorText != ''"></p>
                                <div class="form-group">
                                    <label>Campaign</label>

                                    <multiselect
                                        name="campaign_id"
                                        v-model="fields.campaigns"
                                        :options="campaigns"
                                        :multiple="true"
                                        track-by="id"
                                        label="name"
                                        placeholder="Select Campaign">
                                    </multiselect>

                                </div>
                                <div class="form-group">
                                    <label>Country</label>

                                    <multiselect
                                        name="country_id"
                                        v-model="fields.countries"
                                        :multiple="true"
                                        :options="countries"
                                        label="name"
                                        placeholder="Select Country">
                                    </multiselect>


                                </div>

                                <div class="form-group">
                                    <label>Sale Status</label>
                                    <multiselect
                                        name="salesStatuses"
                                        v-model="fields.salesStatuses"
                                        :options="salesStatuses"
                                        :multiple="true"
                                        track-by=""
                                        label=""
                                        placeholder="Select Sales Status">
                                    </multiselect>
                                </div>

                                <div class="form-group">
                                    <label>Employee</label>

                                    <multiselect
                                            v-model="fields.employees"
                                            :options="employees"
                                            :multiple="true"
                                            track-by="id"
                                            label="firstName"
                                            placeholder="Select Employee">
                                    </multiselect>

                                </div>

                                <div class="form-group">
                                    <div class="col-md-6 date-field">
                                        <label class="col-xs-3">From:</label>
                                        <datepicker v-model="fields.startDate" format="dd/MM/yyyy" placeholder="Start Date" class="form-control col-xs-9"></datepicker>
                                    </div>
                                    <div class="col-md-6 date-field">
                                        <label class="col-xs-3">To:</label>
                                        <datepicker v-model="fields.endDate" format="dd/MM/yyyy" placeholder="End Date" class="form-control col-xs-9"></datepicker>
                                    </div>
                                </div>
                                <div class="clearfix"></div>
                                
                                
                                <div class="form-group pull-left" style="margin-top: 10px;">
                                    <label>Results Per Page</label>                                    
                                    <select v-model="fields.perPage" class="form-control">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                        <option value="250">250</option>
                                    </select>
                                </div>
                                <div class="form-group pull-right" style="margin-top: 40px;">
                                    <button type="submit" class="btn btn-primary" v-text="isSearching ? 'Loading...' : 'SEARCH'" :disabled="isSearching" @click.prevent="searchCustomers"></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="container">
            <p v-if="customers.data && customers.data.length" id="paginationIndicator">
                Showing {{ customers.from }} to {{ customers.to }} out of {{ customers.total }}
            </p>
            <selected-customers 
                v-if="customers.data && customers.data.length" 
                :customers="customers" 
                :countries="countries">
            </selected-customers>

            <div v-if="customers.data && customers.data.length" class="text-center">
                <ul class="pagination">
                    <li v-if="customers.current_page > 1">
                        <a href="#" aria-label="Previous"
                           @click.prevent="changePage(customers.current_page - 1)">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <li v-for="page in pagesNumber"
                        :class="[ page == customers.current_page ? 'active' : '']">
                        <a href="#"
                           @click.prevent="changePage(page)">{{ page }}</a>
                    </li>
                    <li v-if="customers.current_page < customers.last_page">
                        <a href="#" aria-label="Next"
                           @click.prevent="changePage(customers.current_page + 1)">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

    </div>

</template>
<style>

</style>
<script type="text/babel">
    import Multiselect from 'vue-multiselect'
    import Datepicker from 'vuejs-datepicker'
    import validate from 'validate.js';
    import SelectedCustomers from './SearchCustomers.vue'

    export default{
        data(){
            return {
                fields: {
                    campaigns: [],
                    countries: [],
                    salesStatuses: [],
                    employees: [],
                    page: 1,
                    startDate: null,
                    endDate: null,
                    perPage: 25
                },
                customers: [],
                salesStatuses: ["new", "noCall", "checkNumber", "noAnswer", "inTheMoney", "callAgain", "notInterested"],
                offset: 2,
                isSearching: false,
                errorText: ""

            }
        },

        props: {
            campaigns: {
                required: true
            },

            countries: {
                required: true
            },

            employees:{
                required: true
            }
        },

        methods: {
            searchCustomers(){
                let constrains = {
                    campaigns: {
                        presence: true
                    },
                    salesStatuses: {
                        presence: true
                    },
                    countries: {
                        presence: true
                    }
                };

                let errors = validate(this.fields, constrains)
                
                if(errors){
                    let errorsText = "";
                    for(let key in errors){
                        errorsText += errors[key][0] + "<br/>";
                    }
                    this.errorText = errorsText;
                    return false;
                }
                this.errorText = "";

                this.isSearching = true;

                axios.post("/get-customers", this.fields)
                    .then(response => {
                        this.customers = [];
                        this.customers = response.data.customers;
                        this.isSearching = false;
                    })
            },
            changePage(page) {
                this.fields.page = page;
                this.searchCustomers();
            }

        },

        computed: {
            pagesNumber() {
                if (!this.customers.to) {
                    return [];
                }
                var from = this.customers.current_page - this.offset;
                if (from < 1) {
                    from = 1;
                }
                var to = from + (this.offset * 2);
                if (to >= this.customers.last_page) {
                    to = this.customers.last_page;
                }
                var pagesArray = [];
                while (from <= to) {
                    pagesArray.push(from);
                    from++;
                }
                return pagesArray;
            }
        },

        components:{Datepicker, Multiselect}
    }
</script>


<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>

<style>
    .date-field{
        padding-left: 0px;
        padding-right: 0px;
    }
    .date-field input{
        border: none;
        width: 100%;
        cursor: pointer;
    }
    .errors-wrapper{
        color: red;
    }
</style>

