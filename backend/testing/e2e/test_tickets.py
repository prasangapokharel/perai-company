import pytest
from starlette.testclient import TestClient

from testing.fixtures import ticket_payload


def test_create_ticket(client: TestClient, company: dict, auth_headers: dict):
    r = client.post(
        f"/api/v1/company/{company['id']}/tickets",
        json=ticket_payload("Dashboard not loading", "technical"),
        headers=auth_headers,
    )
    assert r.status_code == 201
    data = r.json()
    assert data["issue"] == "Dashboard not loading"
    assert data["category"] == "technical"
    assert data["status"] == "open"
    assert data["company_id"] == company["id"]
    return data


def test_create_ticket_no_auth(client: TestClient, company: dict):
    r = client.post(
        f"/api/v1/company/{company['id']}/tickets",
        json=ticket_payload(),
    )
    assert r.status_code in (401, 403)


def test_list_tickets(client: TestClient, company: dict, auth_headers: dict):
    client.post(
        f"/api/v1/company/{company['id']}/tickets",
        json=ticket_payload("List test ticket"),
        headers=auth_headers,
    )
    r = client.get(f"/api/v1/company/{company['id']}/tickets", headers=auth_headers)
    assert r.status_code == 200
    assert isinstance(r.json(), list)
    assert len(r.json()) >= 1


def test_get_ticket(client: TestClient, company: dict, auth_headers: dict):
    create_r = client.post(
        f"/api/v1/company/{company['id']}/tickets",
        json=ticket_payload("Get test ticket"),
        headers=auth_headers,
    )
    ticket_id = create_r.json()["id"]

    r = client.get(
        f"/api/v1/company/{company['id']}/tickets/{ticket_id}",
        headers=auth_headers,
    )
    assert r.status_code == 200
    assert r.json()["id"] == ticket_id


def test_get_ticket_not_found(client: TestClient, company: dict, auth_headers: dict):
    r = client.get(f"/api/v1/company/{company['id']}/tickets/999999", headers=auth_headers)
    assert r.status_code == 404


def test_update_ticket_status_close(client: TestClient, company: dict, auth_headers: dict):
    create_r = client.post(
        f"/api/v1/company/{company['id']}/tickets",
        json=ticket_payload("Close test ticket"),
        headers=auth_headers,
    )
    ticket_id = create_r.json()["id"]

    r = client.put(
        f"/api/v1/company/{company['id']}/tickets/{ticket_id}",
        json={"status": "closed"},
        headers=auth_headers,
    )
    assert r.status_code == 200
    assert r.json()["status"] == "closed"


def test_update_ticket_issue(client: TestClient, company: dict, auth_headers: dict):
    create_r = client.post(
        f"/api/v1/company/{company['id']}/tickets",
        json=ticket_payload("Original issue"),
        headers=auth_headers,
    )
    ticket_id = create_r.json()["id"]

    r = client.put(
        f"/api/v1/company/{company['id']}/tickets/{ticket_id}",
        json={"issue": "Updated issue"},
        headers=auth_headers,
    )
    assert r.status_code == 200
    assert r.json()["issue"] == "Updated issue"


def test_ticket_history(client: TestClient, company: dict, auth_headers: dict):
    create_r = client.post(
        f"/api/v1/company/{company['id']}/tickets",
        json=ticket_payload("History test ticket"),
        headers=auth_headers,
    )
    ticket_id = create_r.json()["id"]
    client.put(
        f"/api/v1/company/{company['id']}/tickets/{ticket_id}",
        json={"status": "closed"},
        headers=auth_headers,
    )

    r = client.get(
        f"/api/v1/company/{company['id']}/tickets/{ticket_id}/history",
        headers=auth_headers,
    )
    assert r.status_code == 200


def test_ticket_stats(client: TestClient, company: dict, auth_headers: dict):
    r = client.get(f"/api/v1/company/{company['id']}/tickets-stats", headers=auth_headers)
    assert r.status_code == 200


def test_delete_ticket(client: TestClient, company: dict, auth_headers: dict):
    create_r = client.post(
        f"/api/v1/company/{company['id']}/tickets",
        json=ticket_payload("Delete test ticket"),
        headers=auth_headers,
    )
    ticket_id = create_r.json()["id"]

    r = client.delete(
        f"/api/v1/company/{company['id']}/tickets/{ticket_id}",
        headers=auth_headers,
    )
    assert r.status_code == 204

    get_r = client.get(
        f"/api/v1/company/{company['id']}/tickets/{ticket_id}",
        headers=auth_headers,
    )
    assert get_r.status_code == 404
