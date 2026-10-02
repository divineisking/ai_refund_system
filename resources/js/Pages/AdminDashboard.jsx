import React, { useEffect, useState } from 'react';
import { Head } from '@inertiajs/react';

export default function AdminDashboard() {
    const [requests, setRequests] = useState([]);
    const [metrics, setMetrics] = useState({ total: 0, total_requests: 0, approved: 0, denied: 0, escalated: 0 });
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [decisionFilter, setDecisionFilter] = useState('ALL');
    const [selectedRequest, setSelectedRequest] = useState(null);
    const [drawerTab, setDrawerTab] = useState('context');

    // Override Form State
    const [overrideDecision, setOverrideDecision] = useState('APPROVED');
    const [overrideNotes, setOverrideNotes] = useState('');
    const [reviewerEmail, setReviewerEmail] = useState('supervisor@store.com');
    const [overrideSubmitting, setOverrideSubmitting] = useState(false);
    const [overrideMessage, setOverrideMessage] = useState('');

    const fetchRequests = () => {
        setLoading(true);
        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (decisionFilter !== 'ALL') params.append('decision', decisionFilter);

        fetch(`/api/refund-requests?${params.toString()}`)
            .then(res => res.json())
            .then(res => {
                if (res.data) {
                    setRequests(res.data);
                    if (res.metrics) setMetrics(res.metrics);
                } else if (Array.isArray(res)) {
                    setRequests(res);
                }
                setLoading(false);
            })
            .catch(err => {
                console.error("Failed to load refund requests", err);
                setLoading(false);
            });
    };

    useEffect(() => {
        fetchRequests();
    }, [decisionFilter]);

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        fetchRequests();
    };

    const handleApplyOverride = (e) => {
        e.preventDefault();
        if (!selectedRequest) return;
        if (!overrideNotes.trim()) {
            setOverrideMessage('A mandatory justification note is required to apply an override.');
            return;
        }

        setOverrideSubmitting(true);
        setOverrideMessage('');

        fetch(`/api/refund-requests/${selectedRequest.request_id || selectedRequest.id}/override`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                manual_decision: overrideDecision,
                admin_notes: overrideNotes,
                reviewed_by: reviewerEmail,
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data) {
                setOverrideMessage('Override applied successfully.');
                setSelectedRequest(data.data);
                fetchRequests();
            } else {
                setOverrideMessage(data.message || 'Failed to apply override.');
            }
        })
        .catch(err => {
            setOverrideMessage('Error applying override.');
        })
        .finally(() => {
            setOverrideSubmitting(false);
        });
    };

    const getDecisionBadge = (decision) => {
        const d = (decision || '').toUpperCase();
        if (d === 'APPROVED') {
            return 'bg-emerald-100 text-emerald-800 border-emerald-300';
        }
        if (d === 'DENIED') {
            return 'bg-rose-100 text-rose-800 border-rose-300';
        }
        return 'bg-amber-100 text-amber-800 border-amber-300';
    };

    const getStatusBadge = (status) => {
        const s = (status || '').toUpperCase();
        if (s === 'MANUALLY_OVERRIDDEN') {
            return 'bg-purple-100 text-purple-800 border-purple-300';
        }
        if (s === 'PENDING_HUMAN_REVIEW') {
            return 'bg-amber-100 text-amber-800 border-amber-300';
        }
        return 'bg-slate-100 text-slate-700 border-slate-300';
    };

    return (
        <div className="min-h-screen bg-slate-50 text-slate-800 p-4 md:p-8">
            <Head title="Admin Support & Audit Dashboard" />
            
            <div className="max-w-7xl mx-auto space-y-6">
                
                {/* Header */}
                <header className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="text-2xl">⚖️</span>
                            <h1 className="text-2xl font-bold text-slate-900">Admin Audit & Supervisor Dashboard</h1>
                        </div>
                        <p className="text-sm text-slate-500 mt-1">Autonomous AI policy arbitration logs, safety monitoring, and supervisor overrides</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <button 
                            onClick={fetchRequests} 
                            className="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors border border-slate-300"
                        >
                            ↻ Refresh
                        </button>
                        <a 
                            href="/" 
                            className="px-3.5 py-2 text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors border border-blue-200"
                        >
                            Open Customer Portal →
                        </a>
                    </div>
                </header>

                {/* KPI Metrics Summary Cards */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
                        <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Claims</span>
                        <div className="text-3xl font-extrabold text-slate-900 mt-1">{metrics.total ?? metrics.total_requests ?? 0}</div>
                        <span className="text-xs text-slate-500 mt-1 block">100% policy audited</span>
                    </div>
                    <div className="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
                        <span className="text-xs font-semibold text-emerald-600 uppercase tracking-wider block">Approved</span>
                        <div className="text-3xl font-extrabold text-emerald-700 mt-1">{metrics.approved ?? 0}</div>
                        <span className="text-xs text-emerald-600 mt-1 block">Standard & fast-track</span>
                    </div>
                    <div className="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
                        <span className="text-xs font-semibold text-rose-600 uppercase tracking-wider block">Denied</span>
                        <div className="text-3xl font-extrabold text-rose-700 mt-1">{metrics.denied ?? 0}</div>
                        <span className="text-xs text-rose-600 mt-1 block">Final sale & expired</span>
                    </div>
                    <div className="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
                        <span className="text-xs font-semibold text-amber-600 uppercase tracking-wider block">Escalated</span>
                        <div className="text-3xl font-extrabold text-amber-700 mt-1">{metrics.escalated ?? 0}</div>
                        <span className="text-xs text-amber-600 mt-1 block">Human review needed</span>
                    </div>
                </div>

                {/* Filter and Search Bar */}
                <div className="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
                    {/* Decision Filter Tabs */}
                    <div className="flex items-center gap-1.5 self-start md:self-auto overflow-x-auto w-full md:w-auto pb-1 md:pb-0">
                        {['ALL', 'APPROVED', 'DENIED', 'ESCALATED'].map(tab => (
                            <button
                                key={tab}
                                onClick={() => setDecisionFilter(tab)}
                                className={`px-3 py-1.5 text-xs font-bold rounded-lg transition-colors ${
                                    decisionFilter === tab
                                        ? 'bg-slate-900 text-white shadow-sm'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                }`}
                            >
                                {tab}
                            </button>
                        ))}
                    </div>

                    {/* Search Form */}
                    <form onSubmit={handleSearchSubmit} className="flex items-center gap-2 w-full md:w-auto">
                        <input
                            type="text"
                            placeholder="Search customer, order, message..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none w-full md:w-72"
                        />
                        <button
                            type="submit"
                            className="px-3.5 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            Filter
                        </button>
                    </form>
                </div>

                {/* Audit Records Table */}
                <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    {loading ? (
                        <div className="p-12 text-center text-slate-400 text-sm">Loading audit records...</div>
                    ) : requests.length === 0 ? (
                        <div className="p-12 text-center text-slate-400 text-sm">No refund requests found matching filter criteria.</div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead className="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                                    <tr>
                                        <th className="py-3 px-4">Request ID</th>
                                        <th className="py-3 px-4">Customer</th>
                                        <th className="py-3 px-4">Order Details</th>
                                        <th className="py-3 px-4">Verdict</th>
                                        <th className="py-3 px-4">Status</th>
                                        <th className="py-3 px-4">Safety Flags</th>
                                        <th className="py-3 px-4 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {requests.map(req => (
                                        <tr key={req.id || req.request_id} className="hover:bg-slate-50/80 transition-colors">
                                            <td className="py-3 px-4 font-mono font-bold text-slate-700">
                                                {req.request_id}
                                                <span className="block font-sans font-normal text-[10px] text-slate-400">
                                                    {new Date(req.created_at).toLocaleDateString()}
                                                </span>
                                            </td>
                                            <td className="py-3 px-4">
                                                <span className="font-semibold text-slate-800 block">
                                                    {req.customer ? req.customer.name : req.customer_id}
                                                </span>
                                                <span className="text-[10px] text-slate-400">
                                                    {req.customer_id} {req.customer?.risk_tier ? `(${req.customer.risk_tier})` : ''}
                                                </span>
                                            </td>
                                            <td className="py-3 px-4">
                                                <span className="font-medium text-slate-700 block">
                                                    {req.order ? req.order.item_name : req.order_id}
                                                </span>
                                                <span className="text-[10px] text-slate-400">
                                                    {req.order_id} • ${req.order?.price ? Number(req.order.price).toFixed(2) : ''}
                                                </span>
                                            </td>
                                            <td className="py-3 px-4">
                                                <span className={`px-2.5 py-1 rounded-full font-extrabold uppercase border text-[10px] ${getDecisionBadge(req.decision)}`}>
                                                    {req.decision}
                                                </span>
                                                {req.manual_decision && (
                                                    <span className="block text-[10px] text-purple-600 font-semibold mt-0.5">
                                                        Override: {req.manual_decision}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="py-3 px-4">
                                                <span className={`px-2 py-0.5 rounded border text-[10px] font-semibold ${getStatusBadge(req.status)}`}>
                                                    {req.status}
                                                </span>
                                            </td>
                                            <td className="py-3 px-4">
                                                {req.prompt_injection_detected ? (
                                                    <span className="bg-red-100 text-red-700 px-2 py-0.5 rounded font-bold text-[10px] border border-red-300">
                                                        ⚠️ Injection Blocked
                                                    </span>
                                                ) : (
                                                    <span className="text-slate-400 text-[10px]">Clean</span>
                                                )}
                                            </td>
                                            <td className="py-3 px-4 text-right">
                                                <button
                                                    onClick={() => {
                                                        setSelectedRequest(req);
                                                        setDrawerTab('context');
                                                        setOverrideNotes(req.admin_notes || '');
                                                        setOverrideDecision(req.manual_decision || (req.decision === 'ESCALATED' ? 'APPROVED' : req.decision));
                                                        setOverrideMessage('');
                                                    }}
                                                    className="px-3 py-1.5 text-xs font-semibold bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-700 rounded border border-slate-200 hover:border-blue-300 transition-colors"
                                                >
                                                    Inspect →
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

            </div>

            {/* Slide-over Audit Drawer */}
            {selectedRequest && (
                <div className="fixed inset-0 z-50 overflow-hidden bg-slate-900/40 backdrop-blur-sm flex justify-end animate-fadeIn">
                    <div className="w-full max-w-xl bg-white h-full shadow-2xl flex flex-col border-l border-slate-200">
                        
                        {/* Drawer Header */}
                        <div className="p-6 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                            <div>
                                <div className="flex items-center gap-2">
                                    <span className="font-mono text-sm font-bold text-slate-800">{selectedRequest.request_id}</span>
                                    <span className={`px-2 py-0.5 rounded-full font-extrabold uppercase border text-[10px] ${getDecisionBadge(selectedRequest.decision)}`}>
                                        {selectedRequest.decision}
                                    </span>
                                </div>
                                <p className="text-xs text-slate-400 mt-1">Processed {new Date(selectedRequest.created_at).toLocaleString()}</p>
                            </div>
                            <button
                                onClick={() => setSelectedRequest(null)}
                                className="w-8 h-8 rounded-full bg-slate-200 hover:bg-slate-300 flex items-center justify-center text-slate-600 font-bold text-sm"
                            >
                                ✕
                            </button>
                        </div>

                        {/* 5 Forensic Tabs */}
                        <div className="border-b border-slate-200 flex bg-white px-6 gap-2 text-xs font-semibold overflow-x-auto">
                            {[
                                { key: 'context', label: '1. Context' },
                                { key: 'message', label: '2. Message' },
                                { key: 'policy', label: '3. Policy' },
                                { key: 'safety', label: '4. Safety' },
                                { key: 'override', label: '5. Override' },
                            ].map(tab => (
                                <button
                                    key={tab.key}
                                    onClick={() => setDrawerTab(tab.key)}
                                    className={`py-3 px-3 border-b-2 font-medium transition-colors whitespace-nowrap ${
                                        drawerTab === tab.key
                                            ? 'border-blue-600 text-blue-600 font-bold'
                                            : 'border-transparent text-slate-500 hover:text-slate-800'
                                    }`}
                                >
                                    {tab.label}
                                </button>
                            ))}
                        </div>

                        {/* Drawer Content */}
                        <div className="flex-1 overflow-y-auto p-6 space-y-4 text-xs">
                            
                            {/* Tab 1: Context */}
                            {drawerTab === 'context' && (
                                <div className="space-y-4">
                                    <div className="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2">
                                        <h4 className="font-bold text-slate-700 uppercase tracking-wider text-[11px]">Customer Profile</h4>
                                        <div className="grid grid-cols-2 gap-2 text-slate-600">
                                            <div>ID: <span className="font-semibold text-slate-900">{selectedRequest.customer_id}</span></div>
                                            <div>Name: <span className="font-semibold text-slate-900">{selectedRequest.customer?.name || 'N/A'}</span></div>
                                            <div>Risk Tier: <span className="font-semibold text-slate-900">{selectedRequest.customer?.risk_tier || 'N/A'}</span></div>
                                            <div>Fraud Score: <span className="font-semibold text-slate-900">{selectedRequest.customer?.fraud_score ?? 'N/A'}/100</span></div>
                                            <div>Return Rate: <span className="font-semibold text-slate-900">{selectedRequest.customer?.return_rate ? Math.round(selectedRequest.customer.return_rate * 100) + '%' : 'N/A'}</span></div>
                                            <div>Total Orders: <span className="font-semibold text-slate-900">{selectedRequest.customer?.total_orders ?? 'N/A'}</span></div>
                                        </div>
                                    </div>

                                    <div className="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2">
                                        <h4 className="font-bold text-slate-700 uppercase tracking-wider text-[11px]">Verified Order Context</h4>
                                        <div className="grid grid-cols-2 gap-2 text-slate-600">
                                            <div>Order ID: <span className="font-semibold text-slate-900">{selectedRequest.order_id}</span></div>
                                            <div>Item: <span className="font-semibold text-slate-900">{selectedRequest.order?.item_name || 'N/A'}</span></div>
                                            <div>Price: <span className="font-semibold text-slate-900">${selectedRequest.order?.price ? Number(selectedRequest.order.price).toFixed(2) : 'N/A'}</span></div>
                                            <div>Days Delivered: <span className="font-semibold text-slate-900">{selectedRequest.order?.days_since_delivery ?? 'N/A'}d</span></div>
                                            <div>Final Sale: <span className="font-semibold text-slate-900">{selectedRequest.order?.is_final_sale ? 'YES' : 'NO'}</span></div>
                                            <div>Damaged: <span className="font-semibold text-slate-900">{selectedRequest.order?.is_damaged ? 'YES' : 'NO'}</span></div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Tab 2: Message */}
                            {drawerTab === 'message' && (
                                <div className="space-y-4">
                                    <div>
                                        <h4 className="font-bold text-slate-700 uppercase tracking-wider text-[11px] mb-2">Original Customer Message</h4>
                                        <div className="bg-slate-50 border border-slate-200 p-4 rounded-xl text-slate-800 leading-relaxed font-mono">
                                            "{selectedRequest.customer_message}"
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Tab 3: Policy */}
                            {drawerTab === 'policy' && (
                                <div className="space-y-4">
                                    <div className="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2">
                                        <h4 className="font-bold text-slate-700 uppercase tracking-wider text-[11px]">Policy Decision Matrix</h4>
                                        <div className="space-y-1">
                                            <span className="text-slate-500 block">Clause Enforced:</span>
                                            <span className="font-bold text-slate-900 text-sm block">{selectedRequest.policy_clause_triggered}</span>
                                        </div>
                                        <div className="space-y-1 pt-2">
                                            <span className="text-slate-500 block">AI Internal Reasoning:</span>
                                            <p className="text-slate-800 leading-relaxed bg-white p-3 rounded border border-slate-200">
                                                {selectedRequest.internal_reasoning}
                                            </p>
                                        </div>
                                        <div className="space-y-1 pt-2">
                                            <span className="text-slate-500 block">Customer Explanation:</span>
                                            <p className="text-slate-800 leading-relaxed bg-white p-3 rounded border border-slate-200">
                                                {selectedRequest.customer_explanation}
                                            </p>
                                        </div>
                                        <div className="space-y-1 pt-2">
                                            <span className="text-slate-500 block">Suggested Action:</span>
                                            <p className="text-slate-800 font-semibold">
                                                {selectedRequest.suggested_action}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Tab 4: Safety */}
                            {drawerTab === 'safety' && (
                                <div className="space-y-4">
                                    <div className="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
                                        <h4 className="font-bold text-slate-700 uppercase tracking-wider text-[11px]">3-Tier Safety & Injection Shield</h4>
                                        <div>
                                            <span className="text-slate-500 block">Injection Status:</span>
                                            {selectedRequest.prompt_injection_detected ? (
                                                <span className="inline-block mt-1 bg-red-100 text-red-800 px-2.5 py-1 rounded font-bold border border-red-300">
                                                    ⚠️ Prompt Injection Attempt Detected
                                                </span>
                                            ) : (
                                                <span className="inline-block mt-1 bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded font-semibold border border-emerald-300">
                                                    ✓ No Injection Detected
                                                </span>
                                            )}
                                        </div>
                                        <div>
                                            <span className="text-slate-500 block">Matched Injection Flags:</span>
                                            {selectedRequest.prompt_injection_flags && selectedRequest.prompt_injection_flags.length > 0 ? (
                                                <ul className="list-disc pl-5 mt-1 text-red-700 font-mono space-y-0.5">
                                                    {selectedRequest.prompt_injection_flags.map((flag, idx) => (
                                                        <li key={idx}>{flag}</li>
                                                    ))}
                                                </ul>
                                            ) : (
                                                <span className="text-slate-400 italic">None</span>
                                            )}
                                        </div>
                                        <div>
                                            <span className="text-slate-500 block">Hard Invariant Guard:</span>
                                            <span className="text-slate-700">Final sale and &gt;$500 invariants actively verified.</span>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Tab 5: Override */}
                            {drawerTab === 'override' && (
                                <form onSubmit={handleApplyOverride} className="space-y-4 bg-slate-50 p-5 rounded-xl border border-slate-200">
                                    <h4 className="font-bold text-slate-900 uppercase tracking-wider text-[11px]">Supervisor Manual Override</h4>
                                    <p className="text-slate-500 text-xs">
                                        Human supervisors can overrule the autonomous AI decision. Every override requires an audit justification note.
                                    </p>

                                    <div>
                                        <label className="block font-medium text-slate-700 mb-1">Target Verdict:</label>
                                        <div className="flex gap-4">
                                            <label className="flex items-center gap-1.5 cursor-pointer">
                                                <input
                                                    type="radio"
                                                    name="override_decision"
                                                    value="APPROVED"
                                                    checked={overrideDecision === 'APPROVED'}
                                                    onChange={() => setOverrideDecision('APPROVED')}
                                                />
                                                <span className="font-bold text-emerald-700">APPROVED</span>
                                            </label>
                                            <label className="flex items-center gap-1.5 cursor-pointer">
                                                <input
                                                    type="radio"
                                                    name="override_decision"
                                                    value="DENIED"
                                                    checked={overrideDecision === 'DENIED'}
                                                    onChange={() => setOverrideDecision('DENIED')}
                                                />
                                                <span className="font-bold text-rose-700">DENIED</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div>
                                        <label className="block font-medium text-slate-700 mb-1">Mandatory Justification Note:</label>
                                        <textarea
                                            value={overrideNotes}
                                            onChange={(e) => setOverrideNotes(e.target.value)}
                                            placeholder="Explain why this exception is granted or denied..."
                                            className="w-full bg-white border border-slate-300 rounded-lg p-2.5 text-xs h-24 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <label className="block font-medium text-slate-700 mb-1">Supervisor Identifier:</label>
                                        <input
                                            type="text"
                                            value={reviewerEmail}
                                            onChange={(e) => setReviewerEmail(e.target.value)}
                                            className="w-full bg-white border border-slate-300 rounded-lg p-2 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                            required
                                        />
                                    </div>

                                    {overrideMessage && (
                                        <div className={`p-2.5 rounded text-xs font-semibold ${
                                            overrideMessage.includes('successfully') ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                                        }`}>
                                            {overrideMessage}
                                        </div>
                                    )}

                                    <button
                                        type="submit"
                                        disabled={overrideSubmitting || !overrideNotes.trim()}
                                        className="w-full py-2.5 bg-purple-700 hover:bg-purple-800 text-white font-bold rounded-lg transition-colors disabled:opacity-50 text-xs"
                                    >
                                        {overrideSubmitting ? 'Recording Override...' : 'Apply Supervisor Override'}
                                    </button>
                                </form>
                            )}

                        </div>

                    </div>
                </div>
            )}

        </div>
    );
}
