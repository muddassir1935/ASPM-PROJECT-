import os
os.environ['MPLCONFIGDIR'] = os.path.join(os.getcwd(), 'scratch', 'mpl_cache')
os.makedirs(os.environ['MPLCONFIGDIR'], exist_ok=True)
os.makedirs('docs/figures', exist_ok=True)

import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import matplotlib.patches as patches

def generate_all_figures():
    # 1. Benchmark Chart
    # NOTE: the latency values below are ILLUSTRATIVE (no MySQL benchmark run
    # exists yet — the demo machine has no MySQL). The measured figure from the
    # Sprint 4 verification is a 532x speedup; re-run benchmark.php on the demo
    # machine and replace these numbers with measured values before final use.
    fig, ax = plt.subplots(figsize=(7, 3.2), dpi=200)
    categories = ['Direct MySQL DB', 'Redis Cache (Socket)', 'Memory Fallback']
    latencies = [2.450, 0.045, 0.012]
    colors = ['#B42318', '#2B3FE0', '#027A48']
    bars = ax.barh(categories, latencies, color=colors, height=0.5)
    ax.set_xlabel('Average Query Latency (milliseconds) — Lower is Better', fontsize=10, fontweight='bold', color='#0F1419')
    ax.set_title('CampusConnect Timeline Feed Latency Benchmark (Limit 20)', fontsize=11, fontweight='bold', color='#0F1419', pad=12)
    for bar, val in zip(bars, latencies):
        ax.text(val + 0.08, bar.get_y() + bar.get_height()/2, f'{val:.3f} ms', va='center', fontweight='bold', fontsize=9.5, color='#0F1419')
    ax.set_xlim(0, 3.2)
    ax.text(0.0, -0.22, 'Illustrative values — measured speedup 532x (Sprint 4 verification, benchmark.php).',
            transform=ax.transAxes, fontsize=8, style='italic', color='#536471')
    ax.spines['top'].set_visible(False)
    ax.spines['right'].set_visible(False)
    ax.spines['left'].set_color('#CFD9DE')
    ax.spines['bottom'].set_color('#CFD9DE')
    ax.grid(axis='x', linestyle='--', alpha=0.5)
    plt.tight_layout()
    plt.savefig('docs/figures/benchmark_chart.png', dpi=140)
    plt.close()

    # 2. Sprint Velocity & Cumulative Tests
    # NOTE: story points were never tracked during the sprints — the values
    # below are RETROSPECTIVE ESTIMATES, labelled as such on the chart.
    # Test counts are real totals; the 141 -> 153 step came from the Sprint 4
    # verification pass (PR #8, 12 regression tests), not from Sprint 5 work.
    fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(10, 3.8), dpi=140)
    sprints = ['Sprint 1\n(Auth)', 'Sprint 2\n(Posts)', 'Sprint 3\n(Social)', 'Sprint 4\n(Cache/Likes)', 'Sprint 5\n(Polish/Hardening)']
    story_points = [8, 13, 21, 21, 15]
    ax1.bar(sprints, story_points, color='#2B3FE0', width=0.48)
    ax1.set_title('Sprint Story Points (retrospective estimates)', fontsize=10, fontweight='bold', color='#0F1419')
    ax1.set_ylabel('Completed Story Points', fontsize=9, fontweight='bold')
    ax1.spines['top'].set_visible(False)
    ax1.spines['right'].set_visible(False)
    for i, v in enumerate(story_points):
        ax1.text(i, v + 0.6, str(v), ha='center', fontweight='bold', fontsize=9)
    ax1.set_ylim(0, 26)
    ax1.grid(axis='y', linestyle='--', alpha=0.4)

    test_counts = [18, 47, 105, 141, 153]
    ax2.plot(sprints, test_counts, marker='o', color='#177245', linewidth=2.5, markersize=7)
    ax2.set_title('Cumulative Automated Tests Passing', fontsize=10, fontweight='bold', color='#0F1419')
    ax2.set_ylabel('Total Unit Tests', fontsize=9, fontweight='bold')
    ax2.spines['top'].set_visible(False)
    ax2.spines['right'].set_visible(False)
    for i, v in enumerate(test_counts):
        ax2.text(i, v + 4, str(v), ha='center', fontweight='bold', fontsize=9, color='#177245')
    ax2.set_ylim(0, 180)
    ax2.grid(axis='y', linestyle='--', alpha=0.4)
    fig.text(0.5, -0.02, 'Test totals are real; the 141→153 step is the Sprint 4 verification pass (PR #8).',
             ha='center', fontsize=8, style='italic', color='#536471')
    plt.tight_layout()
    plt.savefig('docs/figures/sprint_velocity.png', dpi=140)
    plt.close()

    # 3. System Architecture Diagram
    fig, ax = plt.subplots(figsize=(10, 4.5), dpi=140)
    ax.axis('off')
    ax.set_xlim(0, 100)
    ax.set_ylim(0, 100)

    # Layers
    layers = [
        ("Presentation Layer (Client Browser / Responsive Mobile 360px)", 78, 16, '#ECEEFE', '#2B3FE0'),
        ("Routing & Controllers (index.php, post.php, explore.php, PostController)", 54, 16, '#F7F9FA', '#0F1419'),
        ("Domain Model & Security (User, Post, Like, Follow, Bookmark, Hashtag, Notification, Auth)", 30, 16, '#E9F6EE', '#177245'),
        ("Persistence & Cache Layer (Database Singleton, MySQL / SQLite, Redis / TinyRedisClient)", 6, 16, '#FDECEA', '#D92D20')
    ]

    for label, y, h, bg, border in layers:
        rect = patches.FancyBboxPatch((5, y), 90, h, boxstyle="round,pad=1.5,rounding_size=2",
                                      facecolor=bg, edgecolor=border, linewidth=1.8)
        ax.add_patch(rect)
        ax.text(50, y + h/2, label, ha='center', va='center', fontsize=10, fontweight='bold', color=border)

    # Arrows
    for y_arrow in [74, 50, 26]:
        ax.annotate('', xy=(50, y_arrow - 3), xytext=(50, y_arrow + 3),
                    arrowprops=dict(facecolor='#536471', edgecolor='#536471', arrowstyle='<->', lw=2))

    plt.tight_layout()
    plt.savefig('docs/figures/architecture_diagram.png', dpi=140)
    plt.close()

    # 4. Database ERD Visual
    fig, ax = plt.subplots(figsize=(10, 5.2), dpi=140)
    ax.axis('off')
    ax.set_xlim(0, 100)
    ax.set_ylim(0, 100)

    tables = [
        ("USERS\n(id, name, email, password_hash,\nrole, bio, cover_style, created_at)", 5, 55, 26, 38, '#ECEEFE', '#2B3FE0'),
        ("POSTS\n(id, user_id, content,\ncreated_at, updated_at, deleted_at)", 37, 55, 26, 38, '#F7F9FA', '#0F1419'),
        ("FOLLOWS\n(follower_id, followed_id,\ncreated_at) [PK: (1,2)]", 5, 5, 26, 38, '#E9F6EE', '#177245'),
        ("LIKES\n(user_id, post_id,\ncreated_at) [PK: (1,2)]", 37, 5, 26, 38, '#FDECEA', '#D92D20'),
        ("NOTIFICATIONS\n(id, user_id, actor_id, type,\nreference_id, message, is_read)", 69, 55, 26, 38, '#FFF4E5', '#B54708'),
        ("HASHTAGS & POST_TAGS\n(hashtags: id, tag)\n(post_hashtags: post_id, tag_id)", 69, 5, 26, 38, '#F3F0FF', '#6938EF'),
    ]

    for label, x, y, w, h, bg, border in tables:
        rect = patches.FancyBboxPatch((x, y), w, h, boxstyle="round,pad=1,rounding_size=2",
                                      facecolor=bg, edgecolor=border, linewidth=1.6)
        ax.add_patch(rect)
        ax.text(x + w/2, y + h/2, label, ha='center', va='center', fontsize=8, fontweight='bold', color=border)

    # Connections
    ax.plot([31, 37], [74, 74], color='#2B3FE0', lw=2, linestyle='-') # User -> Post
    ax.plot([18, 18], [55, 43], color='#177245', lw=2, linestyle='-') # User -> Follows
    ax.plot([50, 50], [55, 43], color='#D92D20', lw=2, linestyle='-') # Post -> Likes
    ax.plot([63, 69], [74, 74], color='#B54708', lw=2, linestyle='-') # Post -> Notifications
    ax.plot([63, 69], [24, 24], color='#6938EF', lw=2, linestyle='-') # Post -> Hashtags

    plt.tight_layout()
    plt.savefig('docs/figures/db_erd.png', dpi=140)
    plt.close()

    print("All figures successfully created in docs/figures/!")

if __name__ == '__main__':
    generate_all_figures()
