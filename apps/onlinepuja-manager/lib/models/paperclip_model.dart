class PaperclipCompanyModel {
  final String id;
  final String name;
  final String prefix;
  final String portalUrl;

  const PaperclipCompanyModel({
    required this.id,
    required this.name,
    required this.prefix,
    required this.portalUrl,
  });

  factory PaperclipCompanyModel.fromJson(Map<String, dynamic> json) {
    return PaperclipCompanyModel(
      id: json['id'] as String? ?? 'fb04286e-c64e-45da-b050-5491282f4965',
      name: json['name'] as String? ?? 'VM Studio',
      prefix: json['prefix'] as String? ?? 'VMS',
      portalUrl: json['portalUrl'] as String? ?? 'https://agents.vmstudio.digital/VMS/agents/chief-of-staff/overview',
    );
  }
}

class PaperclipMetricsModel {
  final int totalAgents;
  final int activeMissions;
  final int completedMissions;
  final int blockedMissions;
  final int totalIssues;

  const PaperclipMetricsModel({
    required this.totalAgents,
    required this.activeMissions,
    required this.completedMissions,
    required this.blockedMissions,
    required this.totalIssues,
  });

  factory PaperclipMetricsModel.fromJson(Map<String, dynamic> json) {
    return PaperclipMetricsModel(
      totalAgents: json['totalAgents'] as int? ?? 0,
      activeMissions: json['activeMissions'] as int? ?? 0,
      completedMissions: json['completedMissions'] as int? ?? 0,
      blockedMissions: json['blockedMissions'] as int? ?? 0,
      totalIssues: json['totalIssues'] as int? ?? 0,
    );
  }
}

class PaperclipAgentModel {
  final String id;
  final String name;
  final String role;
  final String? title;
  final String status;
  final bool isPaused;
  final String? pausedAt;
  final String? pauseReason;
  final String? adapterType;
  final String? model;
  final String? avatarUrl;
  final String? urlKey;
  final int budgetMonthlyCents;
  final int spentMonthlyCents;

  const PaperclipAgentModel({
    required this.id,
    required this.name,
    required this.role,
    this.title,
    required this.status,
    this.isPaused = false,
    this.pausedAt,
    this.pauseReason,
    this.adapterType,
    this.model,
    this.avatarUrl,
    this.urlKey,
    this.budgetMonthlyCents = 0,
    this.spentMonthlyCents = 0,
  });

  factory PaperclipAgentModel.fromJson(Map<String, dynamic> json) {
    final adapterConfig = json['adapterConfig'] as Map<String, dynamic>?;
    final modelName = adapterConfig?['model'] as String?;
    final rawStatus = (json['status'] as String? ?? 'idle').toLowerCase();
    final pausedAt = json['pausedAt'] as String?;
    final pauseReason = json['pauseReason'] as String?;
    final isPausedExplicit = json['isPaused'] == true;
    final lifecycleState = (json['lifecycleState'] as String? ?? '').toLowerCase();

    final isPaused = isPausedExplicit ||
        pausedAt != null ||
        rawStatus == 'paused' ||
        lifecycleState == 'paused';

    return PaperclipAgentModel(
      id: json['id'] as String? ?? '',
      name: json['name'] as String? ?? 'Autonomous Agent',
      role: json['role'] as String? ?? 'general',
      title: json['title'] as String?,
      status: isPaused ? 'paused' : rawStatus,
      isPaused: isPaused,
      pausedAt: pausedAt,
      pauseReason: pauseReason,
      adapterType: json['adapterType'] as String?,
      model: modelName,
      avatarUrl: json['avatarUrl'] as String?,
      urlKey: json['urlKey'] as String?,
      budgetMonthlyCents: json['budgetMonthlyCents'] as int? ?? 0,
      spentMonthlyCents: json['spentMonthlyCents'] as int? ?? 0,
    );
  }

  PaperclipAgentModel copyWith({
    String? id,
    String? name,
    String? role,
    String? title,
    String? status,
    bool? isPaused,
    String? pausedAt,
    String? pauseReason,
    String? adapterType,
    String? model,
    String? avatarUrl,
    String? urlKey,
    int? budgetMonthlyCents,
    int? spentMonthlyCents,
  }) {
    final newStatus = status ?? this.status;
    final newIsPaused = isPaused ?? (newStatus.toLowerCase() == 'paused' || (this.isPaused && status == null));
    return PaperclipAgentModel(
      id: id ?? this.id,
      name: name ?? this.name,
      role: role ?? this.role,
      title: title ?? this.title,
      status: newStatus,
      isPaused: newIsPaused,
      pausedAt: pausedAt ?? this.pausedAt,
      pauseReason: pauseReason ?? this.pauseReason,
      adapterType: adapterType ?? this.adapterType,
      model: model ?? this.model,
      avatarUrl: avatarUrl ?? this.avatarUrl,
      urlKey: urlKey ?? this.urlKey,
      budgetMonthlyCents: budgetMonthlyCents ?? this.budgetMonthlyCents,
      spentMonthlyCents: spentMonthlyCents ?? this.spentMonthlyCents,
    );
  }

  String get displayRole {
    if (title != null && title!.trim().isNotEmpty) {
      return title!;
    }
    switch (role.toLowerCase()) {
      case 'general':
        return 'Autonomous Operations Commander & Chief of Staff';
      case 'cmo':
        return 'Chief Marketing Officer & Growth Strategist';
      case 'engineer':
        return 'Chief Technology Officer & Lead Architect';
      case 'pm':
        return 'Head of Client Delivery & QA';
      case 'researcher':
        return 'Organic Search & Programmatic SEO Lead';
      default:
        return role.toUpperCase();
    }
  }

  bool get isChiefOfStaff => id == '70d6fd5d-36f8-4871-9d5b-587131c9f4e6' || role.toLowerCase() == 'general';
}

class PaperclipIssueModel {
  final String id;
  final String identifier;
  final String title;
  final String description;
  final String status;
  final String priority;
  final String? assigneeAgentId;
  final int issueNumber;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  const PaperclipIssueModel({
    required this.id,
    required this.identifier,
    required this.title,
    required this.description,
    required this.status,
    required this.priority,
    this.assigneeAgentId,
    this.issueNumber = 0,
    this.createdAt,
    this.updatedAt,
  });

  factory PaperclipIssueModel.fromJson(Map<String, dynamic> json) {
    return PaperclipIssueModel(
      id: json['id'] as String? ?? '',
      identifier: json['identifier'] as String? ?? 'VMS',
      title: json['title'] as String? ?? 'Untitled Mission',
      description: json['description'] as String? ?? '',
      status: (json['status'] as String? ?? 'todo').toLowerCase(),
      priority: (json['priority'] as String? ?? 'medium').toLowerCase(),
      assigneeAgentId: json['assigneeAgentId'] as String?,
      issueNumber: json['issueNumber'] as int? ?? 0,
      createdAt: json['createdAt'] != null ? DateTime.tryParse(json['createdAt'] as String) : null,
      updatedAt: json['updatedAt'] != null ? DateTime.tryParse(json['updatedAt'] as String) : null,
    );
  }

  bool get isDone => status == 'done' || status == 'completed';
  bool get isInProgress => status == 'in_progress' || status == 'running' || status == 'active';
  bool get isBlocked => status == 'blocked';
  bool get isTodo => status == 'todo' || status == 'backlog' || status == 'open';
}

class PaperclipOverviewModel {
  final PaperclipCompanyModel company;
  final PaperclipMetricsModel metrics;
  final PaperclipAgentModel? chiefOfStaff;
  final List<PaperclipAgentModel> agents;
  final List<PaperclipIssueModel> recentIssues;

  const PaperclipOverviewModel({
    required this.company,
    required this.metrics,
    this.chiefOfStaff,
    required this.agents,
    required this.recentIssues,
  });

  factory PaperclipOverviewModel.fromJson(Map<String, dynamic> json) {
    final companyData = json['company'] as Map<String, dynamic>? ?? {};
    final metricsData = json['metrics'] as Map<String, dynamic>? ?? {};
    final chiefData = json['chiefOfStaff'] as Map<String, dynamic>?;

    final rawAgents = json['agents'] as List<dynamic>? ?? [];
    final agentList = rawAgents
        .whereType<Map<String, dynamic>>()
        .map((a) => PaperclipAgentModel.fromJson(a))
        .toList();

    final rawIssues = json['recentIssues'] as List<dynamic>? ?? [];
    final issueList = rawIssues
        .whereType<Map<String, dynamic>>()
        .map((i) => PaperclipIssueModel.fromJson(i))
        .toList();

    return PaperclipOverviewModel(
      company: PaperclipCompanyModel.fromJson(companyData),
      metrics: PaperclipMetricsModel.fromJson(metricsData),
      chiefOfStaff: chiefData != null ? PaperclipAgentModel.fromJson(chiefData) : null,
      agents: agentList,
      recentIssues: issueList,
    );
  }
}

class PaperclipActivityModel {
  final String id;
  final String actorType;
  final String actorId;
  final String action;
  final String entityType;
  final String entityId;
  final String? agentId;
  final String? runId;
  final Map<String, dynamic> details;
  final DateTime? createdAt;

  const PaperclipActivityModel({
    required this.id,
    required this.actorType,
    required this.actorId,
    required this.action,
    required this.entityType,
    required this.entityId,
    this.agentId,
    this.runId,
    this.details = const {},
    this.createdAt,
  });

  factory PaperclipActivityModel.fromJson(Map<String, dynamic> json) {
    return PaperclipActivityModel(
      id: json['id'] as String? ?? '',
      actorType: json['actorType'] as String? ?? 'system',
      actorId: json['actorId'] as String? ?? '',
      action: json['action'] as String? ?? '',
      entityType: json['entityType'] as String? ?? '',
      entityId: json['entityId'] as String? ?? '',
      agentId: json['agentId'] as String?,
      runId: json['runId'] as String?,
      details: json['details'] as Map<String, dynamic>? ?? {},
      createdAt: json['createdAt'] != null ? DateTime.tryParse(json['createdAt'] as String) : null,
    );
  }

  String get displayTitle {
    final title = details['issueTitle'] as String?;
    final identifier = details['identifier'] as String?;
    if (identifier != null && title != null) {
      return '[$identifier] $title';
    }
    if (identifier != null) return 'Mission $identifier';
    if (action.contains('lease_acquired')) return 'Environment Lease Acquired';
    if (action.contains('lease_released')) return 'Environment Lease Released';
    if (action.contains('cost')) return 'Token Cost Accounting';
    return action.replaceAll('.', ' • ').toUpperCase();
  }

  String get displaySnippet {
    final snippet = details['bodySnippet'] as String?;
    if (snippet != null && snippet.trim().isNotEmpty) {
      return snippet.trim();
    }
    final note = details['resolutionNote'] as String?;
    if (note != null && note.trim().isNotEmpty) {
      return note.trim();
    }
    final model = details['model'] as String?;
    if (model != null) {
      return 'Executed on sovereign LLM: $model';
    }
    return 'Action: $action by $actorType';
  }
}

class PaperclipHeartbeatRunModel {
  final String id;
  final String agentId;
  final String status;
  final String invocationSource;
  final String? triggerDetail;
  final String? startedAt;
  final String? finishedAt;
  final int inputTokens;
  final int outputTokens;
  final String? summary;
  final String? livenessReason;
  final int logBytes;
  final String? model;

  const PaperclipHeartbeatRunModel({
    required this.id,
    required this.agentId,
    required this.status,
    required this.invocationSource,
    this.triggerDetail,
    this.startedAt,
    this.finishedAt,
    this.inputTokens = 0,
    this.outputTokens = 0,
    this.summary,
    this.livenessReason,
    this.logBytes = 0,
    this.model,
  });

  factory PaperclipHeartbeatRunModel.fromJson(Map<String, dynamic> json) {
    final usage = json['usageJson'] as Map<String, dynamic>? ?? {};
    final result = json['resultJson'] as Map<String, dynamic>? ?? {};

    int parseTokens(dynamic val) {
      if (val is int) return val;
      if (val is String) return int.tryParse(val) ?? 0;
      return 0;
    }

    return PaperclipHeartbeatRunModel(
      id: json['id'] as String? ?? '',
      agentId: json['agentId'] as String? ?? '',
      status: (json['status'] as String? ?? 'succeeded').toLowerCase(),
      invocationSource: json['invocationSource'] as String? ?? 'timer',
      triggerDetail: json['triggerDetail'] as String?,
      startedAt: json['startedAt'] as String?,
      finishedAt: json['finishedAt'] as String?,
      inputTokens: parseTokens(json['inputTokens']),
      outputTokens: parseTokens(json['outputTokens']),
      summary: result['summary'] as String?,
      livenessReason: json['livenessReason'] as String?,
      logBytes: json['logBytes'] as int? ?? 0,
      model: usage['model'] as String?,
    );
  }

  bool get isRunning => status == 'running' || status == 'queued';
  bool get isSucceeded => status == 'succeeded' || status == 'done';
  bool get isFailed => status == 'failed' || status == 'error';
}
