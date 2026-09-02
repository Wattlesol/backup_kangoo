<template>
  <div class="order-list">
    <!-- Loading State -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2 text-muted">Loading your orders...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="orders.length === 0" class="text-center py-5">
      <div class="mb-4">
        <i class="fas fa-shopping-bag fa-4x text-muted"></i>
      </div>
      <h4 class="text-muted">No Orders Yet</h4>
      <p class="text-muted mb-4">
        You haven't placed any orders yet. Start shopping to see your orders here.
      </p>
      <a href="/store" class="btn btn-primary">
        <i class="fas fa-shopping-bag me-2"></i>
        Start Shopping
      </a>
    </div>

    <!-- Orders List -->
    <div v-else>
      <div class="row">
        <div class="col-12" v-for="order in orders" :key="order.id">
          <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
              <div class="row align-items-center">
                <div class="col-md-6">
                  <h6 class="mb-0">
                    <i class="fas fa-receipt me-2"></i>
                    Order #{{ order.order_number }}
                  </h6>
                  <small class="text-muted">{{ formatDate(order.created_at) }}</small>
                </div>
                <div class="col-md-6 text-md-end">
                  <span class="badge" :class="getStatusBadgeClass(order.status)">
                    {{ formatStatus(order.status) }}
                  </span>
                  <span
                    class="badge ms-2"
                    :class="getPaymentStatusBadgeClass(order.payment_status)"
                  >
                    {{ formatPaymentStatus(order.payment_status) }}
                  </span>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-8">
                  <!-- Order Items -->
                  <div class="order-items">
                    <div
                      v-for="item in order.items"
                      :key="item.id"
                      class="d-flex align-items-center mb-3"
                    >
                      <div class="flex-shrink-0 me-3">
                        <img
                          :src="item.product_details.image || '/images/default-product.jpg'"
                          class="rounded"
                          style="width: 60px; height: 60px; object-fit: cover"
                          :alt="item.product_name"
                        />
                      </div>
                      <div class="flex-grow-1">
                        <h6 class="mb-1">{{ item.product_name }}</h6>
                        <p class="text-muted mb-1 small">
                          {{ item.product_details.category || 'No category' }}
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                          <span class="text-muted small">Qty: {{ item.quantity }}</span>
                          <span class="fw-bold">{{ formatCurrency(item.total_price) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <!-- Order Summary -->
                  <div class="order-summary">
                    <div class="d-flex justify-content-between mb-2">
                      <span>Subtotal:</span>
                      <span>{{ formatCurrency(order.subtotal) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                      <span>Delivery Fee:</span>
                      <span>{{ formatCurrency(order.delivery_fee) }}</span>
                    </div>
                    <hr />
                    <div class="d-flex justify-content-between fw-bold">
                      <span>Total:</span>
                      <span>{{ formatCurrency(order.total_amount) }}</span>
                    </div>
                  </div>

                  <!-- Order Actions -->
                  <div class="mt-3">
                    <button
                      class="btn btn-outline-primary btn-sm w-100 mb-2"
                      @click="viewOrderDetails(order)"
                    >
                      <i class="fas fa-eye me-2"></i>
                      View Details
                    </button>
                    <button
                      v-if="canCancelOrder(order)"
                      class="btn btn-outline-danger btn-sm w-100"
                      @click="cancelOrder(order)"
                    >
                      <i class="fas fa-times me-2"></i>
                      Cancel Order
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="d-flex justify-content-center mt-4">
        <nav>
          <ul class="pagination">
            <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
              <button
                class="page-link"
                @click="loadOrders(pagination.current_page - 1)"
                :disabled="pagination.current_page === 1"
              >
                Previous
              </button>
            </li>
            <li
              v-for="page in visiblePages"
              :key="page"
              class="page-item"
              :class="{ active: page === pagination.current_page }"
            >
              <button class="page-link" @click="loadOrders(page)">{{ page }}</button>
            </li>
            <li
              class="page-item"
              :class="{ disabled: pagination.current_page === pagination.last_page }"
            >
              <button
                class="page-link"
                @click="loadOrders(pagination.current_page + 1)"
                :disabled="pagination.current_page === pagination.last_page"
              >
                Next
              </button>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  user_id: Number
})

// Reactive data
const orders = ref([])
const loading = ref(true)
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0
})

// Computed properties
const visiblePages = computed(() => {
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const pages = []

  const start = Math.max(1, current - 2)
  const end = Math.min(last, current + 2)

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  return pages
})

// Methods
const loadOrders = async (page = 1) => {
  loading.value = true

  try {
    const response = await fetch(`/api/orders?page=${page}`, {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${getAuthToken()}`
      }
    })

    if (response.ok) {
      const data = await response.json()
      orders.value = data.data.data
      pagination.value = {
        current_page: data.data.current_page,
        last_page: data.data.last_page,
        per_page: data.data.per_page,
        total: data.data.total
      }
    } else {
      console.error('Failed to load orders')
    }
  } catch (error) {
    console.error('Error loading orders:', error)
  } finally {
    loading.value = false
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatCurrency = (amount) => {
  return `ر.س${amount}`
}

const formatStatus = (status) => {
  const statusMap = {
    pending: 'Pending',
    confirmed: 'Confirmed',
    processing: 'Processing',
    shipped: 'Shipped',
    delivered: 'Delivered',
    cancelled: 'Cancelled'
  }
  return statusMap[status] || status
}

const formatPaymentStatus = (status) => {
  const statusMap = {
    pending: 'Pending',
    paid: 'Paid',
    failed: 'Failed',
    refunded: 'Refunded'
  }
  return statusMap[status] || status
}

const getStatusBadgeClass = (status) => {
  const classMap = {
    pending: 'bg-warning',
    confirmed: 'bg-info',
    processing: 'bg-primary',
    shipped: 'bg-secondary',
    delivered: 'bg-success',
    cancelled: 'bg-danger'
  }
  return classMap[status] || 'bg-secondary'
}

const getPaymentStatusBadgeClass = (status) => {
  const classMap = {
    pending: 'bg-warning',
    paid: 'bg-success',
    failed: 'bg-danger',
    refunded: 'bg-info'
  }
  return classMap[status] || 'bg-secondary'
}

const canCancelOrder = (order) => {
  return ['pending', 'confirmed'].includes(order.status)
}

const viewOrderDetails = (order) => {
  // TODO: Implement order details modal or page
  console.log('View order details:', order)
}

const cancelOrder = async (order) => {
  if (!confirm('Are you sure you want to cancel this order?')) {
    return
  }

  try {
    const response = await fetch(`/api/orders/${order.id}/cancel`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        Authorization: `Bearer ${getAuthToken()}`,
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })

    if (response.ok) {
      // Reload orders to reflect the cancellation
      loadOrders(pagination.value.current_page)
    } else {
      alert('Failed to cancel order')
    }
  } catch (error) {
    console.error('Error cancelling order:', error)
    alert('Failed to cancel order')
  }
}

const getAuthToken = () => {
  // Get auth token from localStorage or meta tag
  return (
    localStorage.getItem('auth_token') ||
    document.querySelector('meta[name="api-token"]')?.getAttribute('content') ||
    ''
  )
}

// Lifecycle
onMounted(() => {
  loadOrders()
})
</script>

<style scoped>
.order-list {
  min-height: 400px;
}

.order-items {
  border-right: 1px solid #e9ecef;
  padding-right: 1rem;
}

.order-summary {
  padding-left: 1rem;
}

@media (max-width: 768px) {
  .order-items {
    border-right: none;
    padding-right: 0;
    margin-bottom: 1rem;
  }

  .order-summary {
    padding-left: 0;
    border-top: 1px solid #e9ecef;
    padding-top: 1rem;
  }
}

.card {
  transition: transform 0.2s;
}

.card:hover {
  transform: translateY(-2px);
}
</style>
