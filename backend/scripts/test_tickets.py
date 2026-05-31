"""Test ticket endpoints."""

import requests
import json
from datetime import datetime, timedelta

BASE_URL = "http://localhost:8000"

def test_tickets():
    """Test complete ticket workflow."""
    print("\n" + "="*80)
    print("🎟️  TICKET SYSTEM TEST SUITE")
    print("="*80 + "\n")
    
    # 1. Register company
    print("📝 Step 1: Register Company")
    print("-" * 80)
    company_data = {
        "company_name": f"TicketCorp-{int(datetime.now().timestamp())}",
        "company_email": f"tickets@ticketcorp-{int(datetime.now().timestamp())}.com",
        "password": "secure_password_123",
        "website": "https://ticketcorp.com"
    }
    
    response = requests.post(
        f"{BASE_URL}/api/v1/auth/register",
        json=company_data
    )
    print(f"Status: {response.status_code}")
    company = response.json()
    company_id = company["id"]
    print(f"✓ Company registered: {company_id}")
    print(f"  Name: {company['company_name']}")
    
    # 2. Create ticket - payment issue
    print("\n📝 Step 2: Create Ticket - Payment Issue")
    print("-" * 80)
    ticket1_data = {
        "issue": "Unable to process credit card payment",
        "category": "payment"
    }
    
    response = requests.post(
        f"{BASE_URL}/api/v1/company/{company_id}/tickets",
        json=ticket1_data
    )
    print(f"Status: {response.status_code}")
    ticket1 = response.json()
    ticket1_id = ticket1["id"]
    print(f"✓ Ticket created: {ticket1_id}")
    print(f"  Issue: {ticket1['issue']}")
    print(f"  Category: {ticket1['category']}")
    print(f"  Status: {ticket1['status']}")
    
    # 3. Create ticket - technical issue
    print("\n📝 Step 3: Create Ticket - Technical Issue")
    print("-" * 80)
    ticket2_data = {
        "issue": "API returns 500 error on dashboard",
        "category": "technical"
    }
    
    response = requests.post(
        f"{BASE_URL}/api/v1/company/{company_id}/tickets",
        json=ticket2_data
    )
    print(f"Status: {response.status_code}")
    ticket2 = response.json()
    ticket2_id = ticket2["id"]
    print(f"✓ Ticket created: {ticket2_id}")
    print(f"  Issue: {ticket2['issue']}")
    print(f"  Category: {ticket2['category']}")
    print(f"  Status: {ticket2['status']}")
    
    # 4. Create ticket - general issue
    print("\n📝 Step 4: Create Ticket - General Issue")
    print("-" * 80)
    ticket3_data = {
        "issue": "Need help with account setup"
    }
    
    response = requests.post(
        f"{BASE_URL}/api/v1/company/{company_id}/tickets",
        json=ticket3_data
    )
    print(f"Status: {response.status_code}")
    ticket3 = response.json()
    ticket3_id = ticket3["id"]
    print(f"✓ Ticket created: {ticket3_id}")
    print(f"  Issue: {ticket3['issue']}")
    print(f"  Category: {ticket3['category']} (default)")
    print(f"  Status: {ticket3['status']}")
    
    # 5. List all tickets
    print("\n📝 Step 5: List All Tickets")
    print("-" * 80)
    response = requests.get(
        f"{BASE_URL}/api/v1/company/{company_id}/tickets"
    )
    print(f"Status: {response.status_code}")
    tickets = response.json()
    print(f"✓ Found {len(tickets)} tickets")
    for ticket in tickets:
        print(f"  - #{ticket['id']}: {ticket['issue'][:50]}... ({ticket['category']}) [{ticket['status']}]")
    
    # 6. List tickets by status
    print("\n📝 Step 6: Filter Tickets by Status (open)")
    print("-" * 80)
    response = requests.get(
        f"{BASE_URL}/api/v1/company/{company_id}/tickets",
        params={"status_filter": "open"}
    )
    print(f"Status: {response.status_code}")
    open_tickets = response.json()
    print(f"✓ Found {len(open_tickets)} open tickets")
    
    # 7. List tickets by category
    print("\n📝 Step 7: Filter Tickets by Category (technical)")
    print("-" * 80)
    response = requests.get(
        f"{BASE_URL}/api/v1/company/{company_id}/tickets",
        params={"category_filter": "technical"}
    )
    print(f"Status: {response.status_code}")
    tech_tickets = response.json()
    print(f"✓ Found {len(tech_tickets)} technical tickets")
    for ticket in tech_tickets:
        print(f"  - #{ticket['id']}: {ticket['issue']}")
    
    # 8. Get ticket details
    print("\n📝 Step 8: Get Ticket Details")
    print("-" * 80)
    response = requests.get(
        f"{BASE_URL}/api/v1/company/{company_id}/tickets/{ticket1_id}"
    )
    print(f"Status: {response.status_code}")
    detail = response.json()
    print(f"✓ Ticket #{detail['id']}:")
    print(f"  Issue: {detail['issue']}")
    print(f"  Category: {detail['category']}")
    print(f"  Status: {detail['status']}")
    print(f"  Created: {detail['created_at']}")
    print(f"  Updated: {detail['updated_at']}")
    
    # 9. Update ticket - change issue text
    print("\n📝 Step 9: Update Ticket - Change Issue Text")
    print("-" * 80)
    update_data = {
        "issue": "Unable to process Visa credit card payment - updated"
    }
    
    response = requests.put(
        f"{BASE_URL}/api/v1/company/{company_id}/tickets/{ticket1_id}",
        json=update_data
    )
    print(f"Status: {response.status_code}")
    updated = response.json()
    print(f"✓ Ticket updated:")
    print(f"  New issue: {updated['issue']}")
    print(f"  Updated at: {updated['updated_at']}")
    
    # 10. Update ticket - close it
    print("\n📝 Step 10: Update Ticket - Close Ticket")
    print("-" * 80)
    close_data = {
        "status": "closed"
    }
    
    response = requests.put(
        f"{BASE_URL}/api/v1/company/{company_id}/tickets/{ticket1_id}",
        json=close_data
    )
    print(f"Status: {response.status_code}")
    closed = response.json()
    print(f"✓ Ticket closed:")
    print(f"  Status: {closed['status']}")
    print(f"  Updated at: {closed['updated_at']}")
    
    # 11. Get ticket history
    print("\n📝 Step 11: Get Ticket History")
    print("-" * 80)
    response = requests.get(
        f"{BASE_URL}/api/v1/company/{company_id}/tickets/{ticket1_id}/history"
    )
    print(f"Status: {response.status_code}")
    history = response.json()
    print(f"✓ Ticket history for #{history['ticket_id']}:")
    for record in history['records']:
        print(f"  - Opened: {record['opened_at']}")
        print(f"    Closed: {record['closed_at']}")
    
    # 12. Get ticket stats
    print("\n📝 Step 12: Get Ticket Statistics")
    print("-" * 80)
    response = requests.get(
        f"{BASE_URL}/api/v1/company/{company_id}/tickets-stats"
    )
    print(f"Status: {response.status_code}")
    stats = response.json()
    print(f"✓ Ticket Statistics:")
    print(f"  Total: {stats['total']}")
    print(f"  Open: {stats['open']}")
    print(f"  Closed: {stats['closed']}")
    print(f"  By Category:")
    for category, count in stats['by_category'].items():
        print(f"    - {category}: {count}")
    
    # 13. Reopen ticket (update back to open)
    print("\n📝 Step 13: Reopen Ticket")
    print("-" * 80)
    reopen_data = {
        "status": "open"
    }
    
    response = requests.put(
        f"{BASE_URL}/api/v1/company/{company_id}/tickets/{ticket1_id}",
        json=reopen_data
    )
    print(f"Status: {response.status_code}")
    reopened = response.json()
    print(f"✓ Ticket reopened:")
    print(f"  Status: {reopened['status']}")
    print(f"  Updated at: {reopened['updated_at']}")
    
    # 14. Delete ticket
    print("\n📝 Step 14: Delete Ticket")
    print("-" * 80)
    response = requests.delete(
        f"{BASE_URL}/api/v1/company/{company_id}/tickets/{ticket2_id}"
    )
    print(f"Status: {response.status_code}")
    if response.status_code == 204:
        print(f"✓ Ticket #{ticket2_id} deleted successfully")
    
    # 15. Verify deletion
    print("\n📝 Step 15: Verify Ticket Deleted")
    print("-" * 80)
    response = requests.get(
        f"{BASE_URL}/api/v1/company/{company_id}/tickets/{ticket2_id}"
    )
    print(f"Status: {response.status_code}")
    if response.status_code == 404:
        print(f"✓ Ticket #{ticket2_id} not found (deleted)")
    
    # 16. Final ticket list
    print("\n📝 Step 16: Final Ticket List")
    print("-" * 80)
    response = requests.get(
        f"{BASE_URL}/api/v1/company/{company_id}/tickets"
    )
    print(f"Status: {response.status_code}")
    final_tickets = response.json()
    print(f"✓ Final count: {len(final_tickets)} tickets")
    for ticket in final_tickets:
        print(f"  - #{ticket['id']}: {ticket['issue'][:40]}... ({ticket['status']})")
    
    print("\n" + "="*80)
    print("✅ ALL TICKET TESTS COMPLETED SUCCESSFULLY")
    print("="*80 + "\n")

if __name__ == "__main__":
    test_tickets()
